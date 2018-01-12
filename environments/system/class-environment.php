<?php
require_once 'interface-environment.php';
use maple\environments\FILE;
/**
 * Core Environment class that facilitates in the loading and execution of environments
 * @package Maple Environment
 * @since 1.0
 * @author Rubixcode
 */
class ENVIRONMENT{
	/**
	 * Loccation for environment settings json
	 * @var file path
	 */
	private static $config_location = ROOT."/environments/environments.json";
	/**
	 * TODO : document
	 * @var array
	 */
	private static $environments_list = [];
	/**
	 * TODO : document
	 * @var array
	 */
	private static $environments = [];
	/**
	 * Environment namespace list of all the loaded environment till now in the current instance
	 * @var array
	 */
	private static $environments_loaded = [];
	/**
	 * Ordered Priority List of environments.
	 * array of environment namespaces
	 * @var array
	 */
	private static $priority = [];
	/**
	 * List of allowed methods
	 * @var array
	 */
	private static $methods = [];
	/**
	 * List of environments that support direct handling
	 * @var array
	 */
	private static $direct = [];
	/**
	 * List of all errors that occured in current instance
	 * @var array
	 */
	private static $errors = [];
	/**
	 * Content Dump
	 * @var string
	 */
	private static $content_dump = null;
	/**
	 * If content exists
	 * @var boolean
	 */
	private static $content = false;
	/**
	 * TODO : document
	 * @var array
	 */
	private static $_url = false;
	/**
	 * TODO : document
	 * @var string
	 */
	public static $url_control_panel = "";
	/**
	 * stores the locks depth for the current instance
	 * @var integer
	 */
	private static $_lock_index = 0;

	private static $_config = [];

	/**
	 * initialize the environment
	 * @param  boolean $recursive set true on recursive call to avoid infinite loop
	 * @return
	 */
	public static function initialize($recursive = false){
		try {
			if(!file_exists(self::$config_location)) throw new Exception("environments file does not exist", 1);
			$config = file_get_contents(self::$config_location);
			$config = json_decode($config,true);
			self::$environments_list = $config["environments"];
			self::$environments 	 = array_keys($config["environments"]);
			self::$priority 		 = $config["priority"];
			self::$methods 		 	 = $config["methods"];

			if(isset($config["settings"])){
				self::$_config = $config["settings"];
				self::$url_control_panel = $config["settings"]["url"]."/";
			}

			foreach (self::$environments_list as $key => $value) {
				if(isset($value["direct"]) && $value["direct"]) self::$direct[] = $key;
				if(isset($value["define"])) foreach ($value["define"] as $dkey => $dvalue) {
					if(!defined($dkey))	define("{$dkey}",$dvalue);
				}
				require_once ROOT."/environments/{$value["location"]}";
			}
		} catch (Exception $e) {
			if($recursive){
				if(!file_exists(self::$config_location)){
					file_put_contents(self::$config_location,json_encode([
						"environments"=>[],
						"priority"=>[],
						"methods"=>[],
						// "settings" => [
						// 	"url"		=>	"/setup",
						// 	"username" 	=> "root",
						// 	"password" 	=> "41a3d23e04b4c8c17cf049d608295fdf",
						// 	"updated"	=>	time()
						// ],
					],JSON_PRETTY_PRINT));
				}
				self::initialize(true);
			}
			else throw $e;
		}

		// run diagnostics
		self::diagnostics();

		// test if attempting to connect to environment settings
		if(strrpos(self::url()->current(),self::url()->root(false).self::$url_control_panel) !== false){
			self::bootup();
			die();
		}
	}

	/**
	 * Run Environment diagnostics to treat any kind of pre initialization failure
	 */
	private static function diagnostics(){
		// Test SSL
		if( self::url()->encoding() == "https://" && !isset($_SERVER["HTTPS"]) ){
			header("Location: https://{$_SERVER["HTTP_HOST"]}{$_SERVER["REQUEST_URI"]}");
			die("Redirecting . . .");
		}

		// Test if environment is locked
		if(self::locked()){
			echo file_get_contents(__DIR__."/setup/error/503.html");
			header("Retry-After: 5");
			http_response_code(503);
			die();
		}
		
		// Test if environments file is optimized
		if(array_diff(self::$priority,self::$environments)){
			self::optimize();
			self::initialize();
			return;
		}
		
		// Test if there are active environments loaded else begin bootup
		if(!self::$environments){
			if(!(strrpos(self::url()->current(),self::url()->root(false).self::$url_control_panel) !== false))
			header("Location: ".self::url()->root(false).self::$url_control_panel);
		};
		
		// Test if First Run
		if(!self::$_config){
			self::bootup();
			die();
		}

	}

	/**
	 * Is the passed method name allowed by the environment
	 * @api
	 * @param  string  $method method name
	 * @return boolean
	 */
	public static function is_allowed($method){ return in_array($method,self::$methods); }

	/**
	 * Check if any environment is ready to return content via direct call
	 * @return boolean
	 */
	public static function direct(){
		foreach (self::$priority as $f) {
			if(in_array($f,self::$direct)){
				if (session_status() != PHP_SESSION_NONE) session_commit();
				$return = call_user_func(self::$environments_list[$f]["class"]."::direct");
				if($return){
					self::$content_dump = call_user_func(self::$environments_list[$f]["class"]."::content");
					self::$content = true;
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * return collected content
	 * @return string
	 */
	public static function content(){ return self::$content?self::$content_dump:false; }

	/**
	 * Implement all environments and return if any content exists
	 * @return boolean
	 */
	public static function execute(){
		foreach (self::$priority as $priority) {
			if (session_status() != PHP_SESSION_NONE) session_commit();
			self::load($priority);
			$code = call_user_func(self::$environments_list[$priority]["class"]."::execute");
			if(call_user_func(self::$environments_list[$priority]["class"]."::has_content")){
				self::$content_dump = call_user_func(self::$environments_list[$priority]["class"]."::content");
				self::$content = true;
				return true;
			}
			if($code["type"] == "error") self::$errors[] = $code["error"];
		}
		return false;
	}

	/**
	 * Load a specified environment,
	 * if unable to load it returns false.
	 * NOTE : if the environment has been loaded previously then it will not reload it but still return true.
	 * @api
	 * @param  string $environment environment namespace
	 * @return boolean status
	 */
	public static function load($environment) {
		if(isset(self::$environments_list[$environment])){
			if(in_array($environment,self::$environments_loaded)) return true;
			call_user_func(self::$environments_list[$environment]["class"]."::load");
			self::$environments_loaded[] = $environment;
			return true;
		} else return false;
	}
	/**
	 * Check if an environment is available to be loaded
	 * @api
	 * @param  string $environment environment namespace
	 * @return boolean
	 */
	public static function available($environment){
		if(in_array($environment,["maple/environment"])) return true;
		return isset(self::$environments_list[$environment]);
	}

	/**
	 * Handle error and return suitable response
	 */
	public static function error(){
		$code = 0;
		$code = self::$errors?end(self::$errors):404;
		if(self::$content === false){
			foreach (self::$priority as $priority) {
				$x = call_user_func(self::$environments_list[$priority]["class"]."::error",$code);
				if($x){
					echo $x;
					return true;
				}
			}
		}
		// if no one takes care then handle here
		if(file_exists(__DIR__."/setup/error/{$code}.html"))
			echo file_get_contents(__DIR__."/setup/error/{$code}.html");
		http_response_code($code);
	}

	/**
	 * returns the environments url object to be used
	 * @uses \maple\environments\__URL class
	 * @param  array $param initialize the url object
	 * @return object        url object
	 */
	public static function url($param = null){
		if($param || !self::$_url) {
			self::$_url = new \maple\environments\__URL();
			self::$_url->initialize($param);
		}
		return self::$_url;
	}

	/**
	 * Optimize the environment configuration file
	 */
	private static function optimize(){
		$config = self::$config_location;
		$config_c = file_get_contents($config);
		$config_c = json_decode($config_c,true);
		$config = $config_c;
		$environments_list = $config["environments"];
		$environments 	 = array_keys($config["environments"]);
		$priority 		 = $config["priority"];
		$methods 		 = $config["methods"];

		$missing_envs = array_diff($priority,$environments);
		if($missing_envs){
			$priority = array_diff($priority,$missing_envs);
			$config_c["priority"] = $priority;
			$_methods = [];
			foreach ($environments_list as $env => $conf) $_methods = array_merge($_methods,$conf["methods"]);
			$_junk_methods = array_diff($config["methods"],$_methods);
			$config_c["methods"] = array_diff($config["methods"],$_junk_methods);
			self::lock("maple/environment : optimizing");
				file_put_contents(self::$config_location,json_encode($config_c,JSON_PRETTY_PRINT));
			self::unlock();
		}
	}

	/**
	 * Load bootup and settings file
	 */
	private static function bootup(){ require_once __DIR__."/setup/index.php"; }

	/**
	 * // BUG: doesnt return specified environment
	 * TODO : bug testing
	 * return environment details from available environment namespaces
	 * @api
	 * @param  string $environment environment namespace
	 *     if set to "*" returns details of all the available environment
	 * @return mixed[array/boolean] details or false if doesnt exists
	 */
	public static function details($environment){
		$path = dirname(__DIR__);
		$dir = array_filter(glob("{$path}/*"), 'is_dir');
		$envs = [];
		foreach ($dir as $value) {
			$file = "{$value}/package.json";
			if(file_exists($file)){
				$_environment = json_decode(file_get_contents($file),true);
				$_detailes = $_environment;
				unset($_detailes["maple"]);
				$_environment = isset($_environment["maple"]["environment"])?$_environment["maple"]["environment"]:false;
				$_environment["active"] = in_array($_environment["namespace"],self::$environments);
				$_environment["details"]= $_detailes;
				if($environment == "*"){ if($_environment) $envs[$_environment["namespace"]] = $_environment; }
				else if($environment == $_environment["namespace"]) return $_environment;
			}
		}
		return $environment == "*"?$envs:[];
	}

	/**
	 * activate an environment
	 * @param  string $param environment namespace
	 * @return array        status
	 */
	public static function activate($param){
		$environment = self::details($param["environment"]);
		if($environment){
			if(!$environment["active"]){
				$config = self::configuration();
				$config["environments"][$environment["namespace"]] = [
					"location"	=>	$environment["location"],
					"class"		=>	$environment["class"],
					"direct"	=>	$environment["direct"],
					"methods"	=>	$environment["methods"],
					"define"	=>	$environment["define"],
				];
				if(!$config["priority"]) $config["priority"][] = $environment["namespace"];
				else{
					if(isset($param["set"]) && isset($param["reference"])){
						$set = $param["set"] == "after"?1:0;
						$key = array_search($param["reference"],$config["priority"]);
						$config["priority"] = array_merge(array_slice($config["priority"], 0, $key + $set, true) ,
    							 [$environment["namespace"]] ,
    				 			 array_slice($config["priority"], $key + $set, count($config["priority"]), true)) ;
						$config["priority"] = array_values($config["priority"]);
					}
				}
				$config["methods"] = array_unique(array_merge($config["methods"],$param["method"]));
				self::lock("maple/environment : activate-environment");
					file_put_contents(self::$config_location,json_encode($config,JSON_PRETTY_PRINT));
				self::unlock();
				return [
					"type"		=>	"success",
					"message"	=>	"",
					"environment"=>	$environment,
					"author"	=>	"maple/environment"
				];
			} else return [
				"type"		=>	"error",
				"message"	=> "ERR::ENVIRONMENT_ALREADY_ACTIVE",
				"author"	=>	"maple/environment"
			];
		}else  return [
			"type"		=>	"error",
			"message"	=>	"ERR::INVALID_ENVIRONMENT_NAMESPACE",
			"author"	=>	"maple/environment"
		];
	}

	/**
	 * deactivate an environment
	 * @param  string $environment environment namespace
	 * @return boolean status
	 */
	public static function deactivate($environment){
		$config = self::configuration();
		if(isset($config["environments"][$environment])){
			unset($config["environments"][$environment]);
			self::lock("maple/environment : deactivate-environment");
				file_put_contents(self::$config_location,json_encode($config,JSON_PRETTY_PRINT));
			self::unlock();
			self::optimize();
			return true;
		}
		return false;
	}

	/**
	 * BUG : returns environment security details
	 * @return array environment configuration
	 */
	public static function configuration() { return json_decode(file_get_contents(self::$config_location),true); }

	/**
	 * define a variable globaly from anywhere
	 * - returns true if defined
	 * - return false if already exists
	 * @api
	 * @param  string $name  variable name
	 * @param  mixed[] $value value
	 * @return boolean status
	 */
	public static function define($name,$value) {
		if(defined($name)) return false;
		else{
			define($name,$value);
			return true;
		}
	}

	/**
	 * Serve files
	 * @api
	 * @throws \InvalidArgumentException if $details is not of type 'array'
	 * @throws \InvalidArgumentException if $headers is not of type 'array'
	 * @param  array $details details
	 *        @type mixed[array,string] 'source'	file paths
	 *        @type string	'url'	base url to check for
	 *        @type array 	'allow'	allowed files
	 *        @type array 	'deny'	deny files
	 *        NOTE: allow and deny can take regular expression matches for files
	 * @param  array $headers return header value
	 * @return string content false if nothing
	 */
	public static function serve($details,&$headers = []) {
		if(!is_array($details)) throw new \InvalidArgumentException("Argument #1 must be of type 'array'", 1);
		if(!is_array($headers)) throw new \InvalidArgumentException("Argument #2 must be of type 'array'", 1);

		$details = array_merge([
			"url"		=>	"/",
			"allow"		=>	[".*"],
			"deny"		=>	[],
			"order"		=>	["allow","deny"]
		],$details);
		if(!isset($details["source"]) || (!is_array($details)&&!is_string($details))) throw new \InvalidArgumentException("Argument #1 must contain 'source' of type 'string' or 'array'", 1);
		if(!is_array($details["allow"]) && !is_string($details["allow"])) throw new \InvalidArgumentException("Argument #1 must have 'allow' of type 'string' or 'array'", 1);
		if(!is_array($details["deny"]) && !is_string($details["deny"])) throw new \InvalidArgumentException("Argument #1 must have 'deny' of type 'string' or 'array'", 1);

		$path = str_replace(self::url()->root(),"/",self::url()->current());
		if(strpos($path,$details["url"])===0){
			$path = substr_replace($path,'',0,strlen($details["url"]));
			if(is_string($details["allow"])) $details["allow"] = [$details["allow"]];
			if(is_string($details["deny"])) $details["deny"] = [$details["deny"]];
			foreach($details["allow"] as $key => $value) $details["allow"][$key] = "({$value})";
			foreach($details["deny"] as $key => $value) $details["deny"][$key] = "({$value})";
			$allow	= "/".implode("|",$details["allow"])."/";
			$deny 	= "/".implode("|",$details["deny"])."/";
			$allowed= false;
			if($details["order"]==["allow","deny"]){
				if($allow!="//" && preg_match($allow,$path)) $allowed = true;
				if($deny!="//" && preg_match($deny,$path)) $allowed = false;
			} else if($details["order"]==["deny","allow"]){
				if($deny!="//" && preg_match($deny,$path)) $allowed = false;
				if($allow!="//" && preg_match($allow,$path)) $allowed = true;
			} else throw new \InvalidArgumentException("Argument #1 must have array 'order' that only contains 'allow' and 'deny'", 1);
			if(!$allowed) return false;
			if(is_string($details["source"])) $details["source"] = [$details["source"]];

			$file = false;
			foreach ($details["source"] as $dir) {
				$dir = rtrim($dir,"/");
				if(file_exists($dir.$path) && is_file($dir.$path)){
					$file = $dir.$path;
					$f = new FILE($file);
					$_headers = [
						"Content-Type"		=> $f->mime(),
						"Content-Length"	=> $f->size(),
						"Last-Modified"		=> gmdate("D, d M Y H:i:s",$f->last_modified()),
						"Etag"				=> md5($f->last_modified().$f->location()),
					];
					$headers 					= array_merge($headers,$_headers);
					return $file;
				}
			}
		}
		return false;
	}

	/**
	 * Set headers
	 * @api
	 * @throws \InvalidArgumentException if invalid $headers data type
	 * @param  mixed[string,array] $headers headers
	 * @return array           current headers
	 */
	public static function headers($headers = false){
		if(is_array($headers)) foreach ($headers as $header => $value) header("{$header}: ".(is_array($value)?implode(',',$value):$value) );
		else if(is_string($headers)) header($headers);
		else if(!$headers){}
		else throw new \InvalidArgumentException("Argument #1 must be of type 'string' or 'array'", 1);

		return headers_list();
	}

	// TODO: !important! set the lock feature
	/**
	 * temporarily lock the website from working.
	 * this must be used when updating critical files and avoiding fatal error that can be a security threat.
	 * this function can be cascaded.
	 * NOTE : it locks the complete environment and must be unlocked once the critical function is over.
	 * @api
	 * @throws DomainException if $param is not formatted to specification
	 * @throws InvalidArgumentException if $param is not string
	 * @throws \maple\environment\exceptions\InvalidEnvironmentException if the namespace is not a valid environment
	 * @param  string $param formatted as "namespace : task"
	 *         - @var namespace is callers environment namespace
	 *         - @var task is a simple name that the caller is performing.
	 * this is neccessary for the purpose of debugging and in case of critical failures during locks
	 */
	public static function lock($param){
		if(!is_string($param)) throw new InvalidArgumentException("Argument #1 expected to be of type 'string', but '".gettype($param)."' passed", 1);
		$param = explode(":",$param);
		if(sizeof($param) != 2) throw new DomainException("Argument #1 could not be parsed", 1);
		$param = array_map('trim',$param);
		if(!self::available($param[0])) throw new \maple\environment\exceptions\InvalidEnvironmentException($param[0], 1);
		self::$_lock_index ++;
		// TODO: save lock to file
	}

	/**
	 * remove the temporary lock
	 * NOTE : must be only called when the environment is in lockdown
	 * @api
	 * @throws LogicException when called unexpectedly without calling __CLASS__::lock() first
	 */
	public static function unlock(){
		if(!self::$_lock_index) throw new LogicException(__METHOD__." should be only called after the environment is locked", 1);
		self::$_lock_index--;
		// TODO: pop lock from file
	}

	/**
	 * returns if the environment is locked
	 * @return boolean status
	 */
	public static function locked(){
		if(self::$_lock_index) return true;
		// else TODO: return based on status
		return false;
	}

	/**
	 * attempts to reset the lock
	 */
	public static function reset_lock(){
	}
}

/**
 * do necessary actions for first boot
 */
function first_boot_action(){
	$directory = substr(
		\ROOT,
		strlen(str_replace("\\","/",$_SERVER["DOCUMENT_ROOT"]))
	)."/";
	$urlpath = $_SERVER["REQUEST_URI"];
	$htaccess = new FILE(\ROOT."/.htaccess");
	$config = new FILE(\ENVIRONMENT."/url.json");
	if(!$htaccess->exists()){
		$htaccess_template = new FILE(__DIR__."/assets/htaccess");
		$htaccess_template = $htaccess_template->read();
		$htaccess_template = str_replace("{{ directory }}",$directory,$htaccess_template);
		$htaccess->write($htaccess_template);
	}
	if (!$config->exists()) {
		$config_data = [
			"ENCODING"	=>	$_SERVER["REQUEST_SCHEME"]."://",
			"DOMAIN"	=>	$_SERVER["SERVER_NAME"],
			"BASE"		=>	rtrim($_SERVER["REQUEST_URI"],"/"),
			"DYNAMIC"	=>	true,
		];
		$config->write($config_data);
	}
	if(!$htaccess->exists() || !$config->exists()){
		echo "Please make sure the following location are writable<br>";
		echo "> ".$htaccess->directory()."<br>";
		echo "> ".$config->directory()."<br>";
	}
}
first_boot_action();
$URL = new FILE(\ENVIRONMENT."/url.json");
/**
 * url details for working of environment
 * @var must contain "ENCODING","DOMAIN","BASE","DYNAMIC"
 */
$URL = json_decode($URL->read(),true);

ENVIRONMENT::url($URL);

?>
