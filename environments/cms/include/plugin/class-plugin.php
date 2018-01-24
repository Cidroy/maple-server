<?php
namespace maple\cms;

/**
 * Plugin Handler class
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class PLUGIN{
	/**
	 * Plugin status
	 * @var integer
	 */
	const active = 1;
	const installed = 2;

	/**
	 * Cache Location
	 * @var file-path
	 */
	const cache = \ROOT.\CACHE."/plugin";
	/**
	 * Configuration folder
	 * @var file-path
	 */
	const config_location = \ROOT.\CONFIG."/plugin";
	/**
	 * Path to plugin configuration file
	 * @var file-path
	 */
	const active_file = \ROOT.\CONFIG."/plugin/active-plugins.json";

	/**
	 * Default Plugin file configuration
	 * @var array
	 */
	const default_configuration = [];

	/**
	 * Plugin Null data
	 * @var array
	 */
	const plugin_format = [
		"autoload"	=>	[],
		"filters"	=>	[],
		"shortcodes"=>	[],
		"routers"	=>	[],
		"template"	=>	false,
		"languages"	=>	false,
		"dashboard"	=>	[],
		"menus"		=>	[],
		"widgets"	=>	[],
		"api"		=>	false,
	];

	/**
	 * all plugin loading sources
	 * @var array
	 */
	private static $_sources = [];

	/**
	 * Active plugin list buffer
	 * @var array
	 */
	private static $_buffer = [];

	/**
	 * Stores plugin data
	 * @var array
	 */
	private static $_plugin_data = [
		"autoload"	=>	[], # class=>file
		"filters"	=>	[], # filter=>function
		"shortcodes"=>	[], # code=>function
		"routers"	=>	[], # name=>file
		"templates"	=>	[], # namespace=>folder
		"languages"	=>	[], # namespace=>folder

		"dashboard"	=>	[],	# []
		"menus"		=>	[],	# []
		"widgets"	=>	[], # []

		"api"		=>	[],	# namespace => file
	];

	/**
	 * List of loaded plugins
	 * @var array
	 */
	private static $_loaded = [];

	private static function optimize(){
		if(!file_exists(self::cache) || !is_dir(self::cache)) mkdir(self::cache,0777,true);
		if(!file_exists(self::config_location) || !is_dir(self::config_location)) mkdir(self::config_location,0777,true);
		if(!file_exists(self::active_file)){ file_put_contents(self::active_file,self::default_configuration); }
		self::initialize();
	}

	public static function initialize(){
		try{
			if(!file_exists(self::active_file)) throw new \Exception("Optimization required", 1);
			if(!file_exists(self::cache)) throw new \Exception("Optimization required", 1);
			self::$_buffer = json_decode(file_get_contents(self::active_file),true);
			self::$_buffer = is_array(self::$_buffer)?self::$_buffer:[];
		}catch(\Exception $e){ self::optimize(); }
	}


	/**
	 * Return if a plugin exists or is active
	 * @param  string $namespace plugin namespace
	 * @return boolean            status
	 */
	public static function active($namespace) { return isset(self::$_buffer[$namespace]); }

	/**
	 * Add Plugins Source
	 * @api
	 * @throws \InvalidArgumentException if $source not of type 'string'
	 * @param file-path $source source to plugin folder
	 */
	public static function source($source){
		if(!is_string($source)) throw new \InvalidArgumentException("Argument #1 must be of type string", 1);
		if(file_exists($source) && is_dir($source) && !in_array($source, self::$_sources))
			self::$_sources[] = $source;
	}

	/**
	 * return sources
	 * @api
	 * @return array sources
	 */
	public static function sources() { return self::$_sources; }

	/**
	 * Activate plugin with namespace
	 * $namespace should be formatted as 'namespace/subnamespace@x.x.x'
	 * BUG : does not check for dependency
	 * BUG : does call setup-activate
	 * @permission maple/plugin:plugin|activate
	 * @filter 'plugin|actiated' if plugin is successfully actiated
	 * @filter 'plugin|activation-failed' if plugin activation failes
	 * @maintainance 'maple/plugin activate'
	 * @throws \InsufficientPermissionException for insufficient permission
	 * @throws \RuntimeException if plugin with higher version already installed
	 * @throws \InvalidArgumentException if $namespace not of type 'string'
	 * @param  string $namespace plugin namespace with version
	 * @param array $param additional details
	 * @return boolean            status
	 */
	public static function activate($namespace,$param = []){
		if(!is_string($namespace))	throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!SECURITY::permission("maple/plugin","plugin|activate")) throw new \maple\cms\exceptions\InsufficientPermissionException("Insufficient Permission", 1);

		$namespace = explode("@",$namespace);
		$version = isset($namespace[1])?$namespace[1]:"*";
		$namespace = $namespace[0];
		if(self::active($namespace)){
			$prev_version = self::details($namespace,self::active)["version"];
			if( $prev_version == $version || $version=="*" )
				return true;
			else if(self::version_compare($version,$prev_version) < 0){
				MAPLE::do_filters("plugin|activation-failed",$filter = [
					"namespace"	=>	$namespace,
					"version"	=>	$version,
					"parameters"=>	$param,
				]);
				throw new \RuntimeException("This Plugin is already installed with a higher version", 1);
			}
		}
		$data = [
			"location"	=>	null,
			"version"	=>	0,
			"plugin"	=>	[],
		];
		$sources = isset($param["sources"])?$param["sources"]:self::$_sources;
		foreach ($sources as $source) {
			foreach (FILE::get_folders($source) as $plugin) {
				if(!file_exists($plugin."/package.json")) continue;
				$buffer = json_decode(file_get_contents($plugin."/package.json"),true);
				if(
					( isset($buffer["namespace"]) && $buffer["namespace"] == $namespace ) &&
					(self::version_compare($version,$buffer["version"]) == 0) &&
					isset($buffer["maple"]["maple/cms"])
				){
						$data = [ "location"	=>	$plugin, "version"	=>	$buffer["version"], "plugin" => $buffer ];
						break;
				}
			}
		}
		if($data["location"]===null){
			MAPLE::do_filters("plugin|activation-failed",$filter = [
				"namespace"	=>	$namespace,
				"version"	=>	$version,
				"parameters"=>	$param,
			]);
			return false;
		}
		$activation = isset($buffer["maple"]["maple/cms"]["setup"])?$buffer["maple"]["maple/cms"]["setup"]:false;
		$buffer = json_decode(file_get_contents(self::active_file),true);
		$data["location_local"] = substr($data["location"],strlen(\ROOT));
		$buffer[$namespace] = [
			"version"	=>	$data["version"],
			"path"		=>	$data["location_local"],
		];
		\ENVIRONMENT::lock("maple/cms : maple/plugin activate");
			file_put_contents(self::active_file,json_encode($buffer));
			self::clear_cache();
			if($activation){
				if(isset($activation["load"])){
					if(!is_array($activation["load"])) $activation["load"] = [$activation["load"]];
					foreach ($activation["load"] as $file){
						if(file_exists($data["location"].$file)) require_once $data["location"].$file;
					}
				}
				if(isset($param["install"]) && $param["install"] && $activation["install"]) call_user_func($activation["install"]);
				if(isset($activation["activate"])) call_user_func($activation["activate"]);
			}
			SECURITY::install_permission($namespace,$buffer[$namespace]["path"]);
			MAPLE::do_filters("plugin|activated",$filter = [
				"namespace"	=>	$namespace,
				"version"	=>	$version,
				"parameters"=>	$param,
			]);
		\ENVIRONMENT::unlock();
		self::$_buffer = $buffer;
		return true;
	}

	/**
	 * Deactivate a plugin
	 * $namespace can be formatted as 'namespace/subnamespace@x.x.x'
	 * @permission maple/plugin:plugin|deactivate
	 * @filter 'plugin|deactiated' if plugin is successfully actiated
	 * @maintainance 'maple/plugin deactivate'
	 * @throws \InsufficientPermissionException for insufficient permission
	 * @throws \InvalidArgumentException if $namespace not of type 'string'
	 * @param  string $namespace namespace or namespace with version
	 * @param array $param additional details
	 * @return boolean            status
	 */
	public static function deactivate($namespace,$param = []){
		if(!is_string($namespace))	throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!SECURITY::permission("maple/plugin","plugin|deactivate")) throw new \maple\cms\exceptions\InsufficientPermissionException("Insufficient Permission", 1);

		$namespace = explode("@",$namespace)[0];
		if(!self::active($namespace)) return true;

		$buffer = json_decode(file_get_contents(self::active_file),true);
		$data = $buffer[$namespace];
		$path = \ROOT.$buffer[$namespace]["path"];
		$version = $buffer[$namespace]["version"];
		unset($buffer[$namespace]);
		\ENVIRONMENT::lock("maple/cms : maple/plugin deactivate");
			file_put_contents(self::active_file,json_encode($buffer));
			self::clear_cache();
			if(file_exists($data["path"]."/package.json")){
				$buffer = json_decode(file_get_contents($data["path"]."/package.json"),true);
				$activation = isset($buffer["maple"]["maple/cms"]["setup"])?$buffer["maple"]["maple/cms"]["setup"]:false;
				if($activation){
					if(isset($activation["load"])){
						if(!is_array($activation["load"])) $activation["load"] = [$activation["load"]];
						foreach ($activation["load"] as $file) if(file_exists($data["path"].$file)) require_once $data["path"].$file;
					}
					if(isset($activation["deactivate"])) call_user_func($activation["deactivate"]);
				}
			}
			SECURITY::uninstall_permission($namespace,$path);
			MAPLE::do_filters("plugin|deactivated",$filter = [
				"namespace"	=>	$namespace,
				"version"	=>	$version,
				"parameters"=>	$param,
			]);
		\ENVIRONMENT::unlock();
		self::$_buffer = $buffer;
		return true;
	}

	/**
	 * List all The Plugins in a location with basic details
	 * @param  mixed[] $sources string or array of location,
	 * '*' for all current list
	 * @return array          plugin list
	 */
	public static function list_all($sources = "*"){
		if($sources=="*") $sources = self::$_sources;
		else if(is_string($sources)) $sources = [$sources];
		$plugins = [];
		foreach ($sources as $source) {
			foreach (FILE::get_folders($source) as $plugin) {
				$buffer = json_decode(file_get_contents($plugin."/package.json"),true);
				if( isset($buffer["maple"]["maple/cms"]) && isset($buffer["namespace"]) ){
					$buffer["core"]	= isset($buffer["maple"]["maple/cms"]["core"]) && $buffer["maple"]["maple/cms"]["core"];
					unset($buffer["maple"]);
					$buffer["id"] = $buffer["namespace"]."@".$buffer["version"];
					$buffer["active"] = isset(self::$_buffer[$buffer["namespace"]]) && self::$_buffer[$buffer["namespace"]]["version"]==$buffer["version"];
					$plugins[] = $buffer;
				}
			}
		}
		return $plugins;
	}

	/**
	 * Plugin description
	 * return false if not found
	 * @api
	 * @throws \InvalidArgumentException if $namespace not of type 'string'
	 * @param  string $namespace namespace or namespace with version
	 * @param  integer $type     plugin status
	 * @return array             data
	 */
	public static function details($namespace,$type = null){
		if(!is_string($namespace))	throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if($type===null) $type = self::installed;
		$namespace = explode("@",$namespace);
		$version = isset($namespace[1])?$namespace[1]:"*";
		$namespace = $namespace[0];
		$path = null;
		switch ($type) {
			case self::active:
					if(isset(self::$_buffer[$namespace]) && self::version_compare(self::$_buffer[$namespace]["version"],$version)==0) {
						$path = self::$_buffer[$namespace]["path"];
					}
				break;
			case self::installed:
				foreach (self::$_sources as $source) {
					foreach (FILE::get_folders($source) as $plugin) {
						$buffer = json_decode(file_get_contents($plugin."/package.json"),true);
						if(
							( isset($buffer["namespace"]) && $buffer["namespace"] == $namespace ) &&
							(self::version_compare($version,$buffer["version"]) == 0) &&
							isset($buffer["maple"]["maple/cms"])
						){ $path = $plugin; }
					}
				}
				break;
		}
		if($path===null) return false;
		$details = json_decode(file_get_contents($path."/package.json"),true);
		$details["core"]	= isset($details["maple"]["maple/cms"]["core"]) && $details["maple"]["maple/cms"]["core"];
		$details["id"] = $details["namespace"]."@".$details["version"];
		$details["active"] = isset(self::$_buffer[$details["namespace"]]) && self::$_buffer[$details["namespace"]]["version"]==$details["version"];
		$details["maple/cms"] = $details["maple"]["maple/cms"];
		$details["path"]	= substr($path,strlen(\ROOT));
		unset($details["maple"]);
		return $details;
	}

	/**
	 * Compare two versions
	 * if $lhs < $rhs => result < 0
	 * if $lhs > $rhs => result > 0
	 * if $lhs = $rhs => result = 0
	 * BUG : Does Nothing
	 * @api
	 * @throws \InvalidArgumentException if $lhs and $rhs not of type 'string'
	 * @param  string $lhs version 1
	 * @param  string $rhs version 2
	 * @return integer      difference
	 */
	public static function version_compare($lhs,$rhs){
		if(!is_string($lhs))	throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($rhs))	throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		$lhs = explode(".",$lhs); $rhs = explode(".",$rhs);
		$count_min = count($lhs)<=count($rhs)?count($lhs):count($rhs);
		$count_max = count($lhs)>count($rhs)?count($lhs):count($rhs);
		for ($i=0; $i < $count_min; $i++) {
			if($lhs[$i]=="*" || $rhs[$i]=="*") return 0;
			else if( (intval($lhs[$i]) < intval($rhs[$i]))||(intval($lhs[$i]) > intval($rhs[$i])) ) return intval($lhs[$i]) - intval($rhs[$i]);
		}
		for ($i=$count_min; $i < $count_max; $i++) {
			if(!isset($lhs[$i])) $lhs[$i] = 0;
			if(!isset($rhs[$i])) $rhs[$i] = 0;
			if($lhs[$i]=="*" || $rhs[$i]=="*") return 0;
			else if( (intval($lhs[$i]) < intval($rhs[$i]))||(intval($lhs[$i]) > intval($rhs[$i])) ) return intval($lhs[$i]) - intval($rhs[$i]);
		}
		return 0;
	}

	/**
	 * Load Plugin details to memory
	 * @api
	 * @throws \InvalidArgumentException if $namespace not of type 'string'
	 * @param  string $namespace activae plugin namespace
	 * also takes '*' to load plugin from sources
	 * @return boolean status
	 */
	public static function load($namespace) {
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if($namespace == "*"){
			// TODO : create a source and active plugin based cache file
			$file = md5(serialize(array_keys(self::$_buffer)).serialize(self::$_sources));
			$file = self::cache."/plugin-{$file}.json";
			if(file_exists($file)){
				self::$_plugin_data = json_decode(file_get_contents($file),true);
				return true;
			}
			foreach (self::$_buffer as $namespace => $details) self::_load($namespace);
			file_put_contents($file,json_encode(self::$_plugin_data));
			return true;
		}else{
			if(!array_key_exists($namespace,self::$_buffer)) return false;
			return self::_load($namespace);
		}
	}

	/**
	 * Individually load a plugin
	 *
	 * @param string $namespace plugin namespace
	 * @return void
	 */
	private static function _load($namespace){
		if(in_array($namespace,self::$_loaded)) return true;
		$plugin = \ROOT.self::$_buffer[$namespace]["path"];
		if(!file_exists($plugin."/package.json")) return false;
		$data = json_decode(file_get_contents($plugin."/package.json"),true);
		if(!isset($data["maple"]["maple/cms"])) return false;
		$data = $data["maple"]["maple/cms"];
		$data = array_merge(self::plugin_format,$data);

		foreach ($data["autoload"] as $class => $file) { $data["autoload"][$class] = $plugin.$file; }
		self::$_plugin_data["autoload"] = array_merge(self::$_plugin_data["autoload"],$data["autoload"]);
		foreach ($data["filters"] as $filter => $function){
			if(!isset(self::$_plugin_data["filters"][$filter])) self::$_plugin_data["filters"][$filter] = [];
			self::$_plugin_data["filters"][$filter][] = [
				"function"	=>	$function,
				"args"		=>	[],
			];
		}
		self::$_plugin_data["shortcodes"] = array_merge(self::$_plugin_data["shortcodes"],$data["shortcodes"]);
		foreach ($data["routers"] as $name => $router) {
			if(!isset(self::$_plugin_data["routers"][$name])) self::$_plugin_data["routers"][$name] = [];
			self::$_plugin_data["routers"][$name][] = $plugin.$router;
		}
		self::$_plugin_data["templates"][$namespace] = $plugin.$data["template"];
		self::$_plugin_data["languages"][$namespace] = $data["languages"];
		self::$_plugin_data["dashboard"] = array_merge(self::$_plugin_data["dashboard"],$data["dashboard"]);
		self::$_plugin_data["menus"] = array_merge(self::$_plugin_data["menus"],$data["menus"]);
		self::$_plugin_data["widgets"] = array_merge(self::$_plugin_data["widgets"],$data["widgets"]);
		self::$_plugin_data["api"][$namespace] = $plugin.$data["api"];

		self::$_loaded[] = $namespace;
		return true;
	}

	/**
	 * Check if Plugin is loaded
	 * @api
	 * @throws \InvalidArgumentException if $namespace is not of type 'string'
	 * @param  string $namespace namespace
	 * @return boolean            status
	 */
	public static function loaded($namespace){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		return in_array($namespace,self::$_loaded);
	}

	/**
	 * Returns Plugin Data
	 * @api
	 * @return array
	 */
	public static function get(){ return self::$_plugin_data; }

	/**
	 * Clear Plugin Data
	 * @api
	 */
	public static function clear(){ foreach (self::$_plugin_data as $key => $value) self::$_plugin_data[$key] = []; }

	/**
	 * Install New Plugin from Market
	 * $namespace should be formatted as 'namespace/subnamespace@x.x.x'
	 * BUG : Does Nothing
	 * @permission maple/plugin:plugin|install
	 * @filter 'plugin|installed' if plugin successfully installs
	 * @filter 'plugin|install-failed' if plugin install failes
	 * @maintainance 'maple/plugin install'
	 * @throws \InsufficientPermissionException for permission
	 * @throws \InsufficientPermissionException for permission
	 * @throws \InvalidArgumentException if $namespace or $source not of type 'string'
	 * @throws \DomainException if $namespace is not properly foramtted
	 * @param  string $namespace plugin namespace with version
	 * @param  string $source url to another marketplace
	 * @return boolean            status
	 */
	public static function install($namespace,$source = null){ }

	/**
	 * Unistall a specific plugin
	 * $namespace should be formatted as 'namespace/subnamespace@x.x.x'
	 * BUG : Does Nothing
	 * @permission maple/plugin:plugin|uninstall
	 * @filter 'plugin|uninstalled' if plugin is successfully uninstalled
	 * @filter 'plugin|uninstall-failed' if plugin uninstall failes
	 * @maintainance 'maple/plugin uninstall'
	 * @throws \InsufficientPermissionException for insufficient permission
	 * @throws \InvalidArgumentException if $namespace not of type 'string'
	 * @throws \InsufficientPermissionException if attempting core plugin uninstall
	 * @throws \DomainException if $namespace is not properly foramtted
	 * @throws \RuntimeException if plugin not installed
	 * @param  string $namespace plugin namespace with version
	 * @return boolean            status
	 */
	public static function uninstall($namespace){ }

	/**
	 * Update a Plugin to its latest version
	 * @permission maple/plugin:plugin|update
	 * @filter 'plugin|updated' if plugin is successfully updated
	 * @filter 'plugin|update-failed' if plugin update failes
	 * @maintainance 'maple/plugin update'
	 * @throws \InsufficientPermissionException for insufficient permission
	 * @throws \InvalidArgumentException if $namespace not of type 'string'
	 * @param  string $namespace namespace
	 * @param  string $source    marketplace
	 * @return boolean            status
	 */
	public static function update($namespace,$source = null){}

	/**
	 * Get Path of Active Plugin using namespace
	 * @api
	 * @param  string $namespace namespace
	 * @return mixed            file path
	 * 'false' if plugin not active
	 */
	public static function path($namespace){ return isset(self::$_buffer[$namespace])?self::$_buffer[$namespace]["path"]:false;}

	/**
	 * Return debug Info
	 * @return array info
	 */
	public static function debug(){
		if(\DEBUG) return [
			"installed"		=>	self::$_buffer,
			"sources"		=>	self::$_sources,
			"loaded"		=>	self::$_loaded,
			"plugin-data"	=>	self::$_plugin_data,
		];
	}

	/**
	 * Clear Cache Files
	 */
	public static function clear_cache(){
		FILE::delete_folder(self::cache);
	}

}

PLUGIN::initialize();

?>
