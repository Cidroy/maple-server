<?php
namespace maple\www;
use \maple\environments\eWWW;

class WWW{
	const _conf_location = __DIR__."/www.json";
	const _conf_folder = __DIR__."/configurations/";
	private static $_conf_temp = null;

	public static function configuration_template(){
		return [
			"use"		=>	false,
			"web-root"	=>	"/",
			"credentials"=>	false,
			"configurations"=>false,
			"optimisation"=>false,
		];
	}

	public static function configuration_location(){ return self::_conf_location; }

	public static function configuration($param = null){
		try {
			if($param === null){
				if(self::$_conf_temp !== null)	return self::$_conf_temp;
				$config_template = self::configuration_template();
				$config = file_get_contents(self::configuration_location());
				if($config){
					$config = json_decode($config,true);
					$config = array_merge($config_template,$config);
					self::$_conf_temp = $config;
					return $config;
				} else{
					self::$_conf_temp = false;
					return false;
				}
			} else {
				if(self::$_conf_temp === null) self::configuration();
				if(self::$_conf_temp["configurations"] && isset($_conf_temp["configurations"][$param])){
					$conf = self::$_conf_temp["configurations"][$param];
					$conf["configuration"] = json_decode(file_get_contents(self::_conf_folder.$conf["configuration"].".json"),true);
					return $conf;
				} else if( is_dir(ROOT.self::$_conf_temp["base-folder"].$param) && file_exists(ROOT.self::$_conf_temp["base-folder"].$param."/package.json")){
					$conf = json_decode(file_get_contents(ROOT.self::$_conf_temp["base-folder"].$param."/package.json"),true);
					$conf = isset($conf["maple"])?(isset($conf["maple"]["maple/www"])?$conf:false):false;
					if($conf){
						$temp = $conf;
						unset($temp["maple"]);
						$conf = $conf["maple"]["maple/www"];
						if(isset(self::$_conf_temp["configurations"][$param]))	$conf["url"] = self::$_conf_temp["configurations"][$param]["url"];
						$conf["details"] = $temp;
						return $conf;
					}
					return false;
				}
				return false;
			}
		} catch (\Exception $e) {
		}

	}

	public static function sites($param){
		switch ($param) {
			case 'active': return self::configuration()["configurations"]; break;
			case '*':
				$all = [];
				foreach (array_filter(glob(ROOT.self::$_conf_temp["base-folder"]."*"), 'is_dir') as $dir) {
					$package = "{$dir}/package.json";
					if(file_exists($package)){
						$package = file_get_contents($package);
						$package = json_decode($package,true);
						if(isset($package["maple"]) && isset($package["maple"]["maple/www"])){
							$package["maple"]["maple/www"]["location"] = str_replace(ROOT.self::$_conf_temp["base-folder"],"",$dir);
							$all[$package["maple"]["maple/www"]["location"]] = $package["maple"]["maple/www"];
						}
					}
				}
				return array_merge($all,self::sites("active"));
				break;
			default: return false; break;
		}
	}

	/**
	 * [save description]
	 * @maintainance 'save-environment-config'
	 * @param  [type] $configuration [description]
	 * @return [type]                [description]
	 */
	protected static function save($configuration){
		$original = self::configuration();
		$save = array_merge($original,$configuration);
		\ENVIRONMENT::lock("maple/www : save-environment-config");
			file_put_contents(self::_conf_location,json_encode($save,JSON_PRETTY_PRINT));
		\ENVIRONMENT::unlock();
	}

	/**
	 * [save_site_configuration description]
	 * @maintainance 'save-site-config'
	 * @param  [type] $location [description]
	 * @param  [type] $config   [description]
	 * @return [type]           [description]
	 */
	protected static function save_site_configuration($location,$config) {
		$location = self::_conf_folder.md5($location).".json";
		$config_template = [
			"index"		=>	null,
			"app-route"	=>  [
				"active"	=>	false,
				"use"		=>	"index",
			],
			"execute"	=>	false,
			"executable"=>	[],
			"files"		=>	[],
			"error-files"=>	[],
		];
		$config = array_merge($config_template,$config);
		\ENVIRONMENT::lock("maple/www : save-site-config");
			file_put_contents($location,json_encode($config,JSON_PRETTY_PRINT));
		\ENVIRONMENT::unlock();
	}
}

class SITE extends WWW{
	public static function activate($param){
		if(!isset($param["url"]) || !isset($param["location"])) throw new \Exception("ERR::INSUFFICIENT_PARAM", 1);
		$site_config = WWW::configuration($param["location"]);
		if(!$site_config) throw new \Exception("ERR::INVALID_SITE_LOCATION", 1);
		$config = WWW::configuration();
		if(in_array($param["location"],array_keys($config["configurations"]))) throw new \Exception("ERR::CONFIG_ALREADY_ACTIVE", 1);
		if(in_array($param["url"],array_keys($config["optimisation"]))) throw new \Exception("ERR::URL_CONFLICT", 1);
		if(!\ENVIRONMENT::url()->available("maple/www","/{$param["url"]}")) throw new \Exception("Url not available please try another.", 1);
		$config["optimisation"][$param["url"]] = $param["location"];
		$config["configurations"][$param["location"]] = [
			"name"	=>	$site_config["name"],
			"url"=>$param["url"],
			"location"=>$param["location"],
			"configuration"=>md5($param["location"]),
		];
		$site_config = $site_config["configuration"];
		WWW::save($config);
		WWW::save_site_configuration($param["location"],$site_config);
		\ENVIRONMENT::url()->register("maple/www","/{$param["url"]}");
		return true;
	}
	public static function edit($param){
		if(!isset($param["url"]) || !isset($param["location"])) throw new \Exception("ERR::INSUFFICIENT_PARAM", 1);
		$site_config = WWW::configuration($param["location"]);
		$config = WWW::configuration();
		if(!in_array($param["location"],array_keys($config["configurations"]))) throw new \Exception("ERR::CONFIG_NOT_ACTIVE", 1);
		if(in_array($param["url"],array_keys($config["optimisation"])) && $config["optimisation"][$param["url"]] != $param["Location"] ) throw new \Exception("ERR::URL_CONFLICT", 1);
		if(!\ENVIRONMENT::url()->available("maple/www","/{$param["url"]}")) throw new Exception("Url not available please try another.", 1);
		$config["optimisation"][$param["url"]] = $param["location"];
		unset($config["optimisation"][$config["configurations"][$param["location"]]["url"]]);
		$config["configurations"][$param["location"]]["url"] = $param["url"];
		WWW::save($config);
		\ENVIRONMENT::url()->unregister("maple/www","/{$site_config["url"]}");
		\ENVIRONMENT::url()->register("maple/www","/{$param["url"]}");
		return true;
	}
	public static function disable($param){
		if(!isset($param["url"]) || !isset($param["location"])) throw new \Exception("ERR::INSUFFICIENT_PARAM", 1);
		$site_config = WWW::configuration($param["location"]);
		$config = WWW::configuration();
		if(!in_array($param["location"],array_keys($config["configurations"]))) throw new \Exception("ERR::CONFIG_NOT_ACTIVE", 1);
		unset($config["optimisation"][$config["configurations"][$param["location"]]["url"]]);
		unset($config["configurations"][$param["location"]]);
		WWW::save($config);
		\ENVIRONMENT::url()->unregister("maple/www","/{$param["url"]}");
		return true;
	}
}

?>
