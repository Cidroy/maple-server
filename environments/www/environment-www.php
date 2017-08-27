<?php
namespace maple\environments;
use \maple\www\WWW;
require_once __DIR__."/class-www.php";

class eWWW implements iRenderEnvironment{
	private static $_www_config = false;
	private static $content = false;
	private static $active = false;
	private static $content_dump = false;

	public static function initialize(){
		try {
			if(!self::$_www_config){
				if(!file_exists(WWW::configuration_location())) throw new \Exception("Configuration file missing", 1);
				$config = WWW::configuration();
				if($config){
					$partial = str_replace(\ENVIRONMENT::url()->root(false),"",\ENVIRONMENT::url()->current());
					foreach ($config["optimisation"] as $link => $dir)
					if(strrpos($partial,$link)!==false || $partial==$link){
						$config["use"] = $dir;
						$config["web-root"] = $link;
					}
					self::$_www_config = $config;
					if(self::$_www_config["use"]){
						$site_config = WWW::configuration($config["use"])["configuration"];
						self::$_www_config = array_merge(self::$_www_config,$site_config);
						self::$active = true;
					}
					\ENVIRONMENT::define("WWW",$config["base-folder"].$config["use"].(substr($config["use"],-1)=="/"?"":"/"));
				} else return false;
			}
		} catch (\Exception $e) {
			self::optimize();
		}
	}

	public static function optimize(){
		try {
			if(!file_exists(WWW::configuration_location())){
				file_put_contents(WWW::configuration_location(),json_encode(WWW::configuration_template(),JSON_PRETTY_PRINT));
			}
		} catch (Exception $e) {
			// TODO : handle 500 with $e->getMessage();
		}
	}

	public static function load(){
	}

	public static function execute() {
		$content = self::settings();
		if(!self::$_www_config["use"] && !$content) return false;
		if(self::$_www_config["use"] && self::$_www_config["app-route"]["active"] && $content===false){
			$file = self::$_www_config["app-route"]["use"] == "index" ?
						self::$_www_config["index"] :
						self::$_www_config["app-route"]["use"];
			if(in_array($file, self::$_www_config["executable"]) && self::$_www_config["execute"]  && file_exists($file)){
				ob_start();
				require_once \ROOT.\WWW.$file;
				$content = ob_get_contents();
				ob_end_clean();
			}
			else if( file_exists(\ROOT.\WWW.$file) ) $content = file_get_contents(\ROOT.\WWW.$file);
		}
		if($content !== false){
			self::$content_dump = $content;
			self::$content = true;
		}
	}

	public static function direct(){
		if(!self::$active) return false;
		$__url = [];
		$__url["%CURRENT%"] = \ENVIRONMENT::url()->current();
		$__url["%WWW%"] 	= \ENVIRONMENT::url()->root(false).self::$_www_config["web-root"];
		$page  = str_replace($__url["%WWW%"], "", $__url["%CURRENT%"]);
		$__file = \ROOT.\WWW.$page;
		$content = false;

		if(file_exists($__file)){
			if(is_dir($__file)){
				if(file_exists("{$__file}/index.html")) $content = file_get_contents("{$__file}/index.html");
			}
			else if( in_array($page, self::$_www_config["executable"]) && self::$_www_config["execute"] ){
				ob_start();
				require_once $__file;
				$content = ob_get_contents();
				ob_end_clean();
			}
			else {
				$file = new \maple\environments\FILE($__file);
				$content = $file->read();
				header("Content-Type: {$file->mime()}");
			}
			if($content){
				self::$content_dump = $content;
				return true;
			}
		}
		return false;
	}

	public static function has_content(){
		return self::$content;
	}

	public static function content(){ return self::$content_dump; }

	public static function error($file){
		$type = "error-files";
		if(isset(self::$_www_config[$type]))
			if(isset(self::$_www_config[$type][$file]))
				if(file_exists(\ROOT.\WWW.self::$_www_config[$type][$file])){
					if(in_array(self::$_www_config[$type][$file], self::$_www_config["executable"]) && self::$_www_config["execute"]){
						$content = false;
						ob_start();
						require_once \ROOT.\WWW.self::$_www_config[$type][$file];
						$content = ob_get_contents();
						ob_end_clean();
						return $content;
					}
					else { return file_exists(\ROOT.\WWW.self::$_www_config[$type][$file])?file_get_contents(\ROOT.\WWW.self::$_www_config[$type][$file]):""; }
				}
	}

	public static function settings(){
		$__url = [];
		$__url["%CURRENT%"] = \ENVIRONMENT::url()->current();
		$__url["%WWW%"] 	= \ENVIRONMENT::url()->root(false).self::$_www_config["web-root"];
		$page = str_replace($__url["%WWW%"], "", $__url["%CURRENT%"]);
		if(!self::$_www_config["use"] && !self::$_www_config["credentials"] && false !== strrpos($page,str_replace(\ENVIRONMENT::url()->root(),"",self::url("install")))){
			ob_start();
				require_once __DIR__."/settings/setup/index.php";
				$content = ob_get_contents();
			ob_end_clean();
			return $content;
		} else if (self::$_www_config["credentials"] && self::$_www_config["credentials"]["url"] && false !== strrpos($page,str_replace(\ENVIRONMENT::url()->root(),"",self::url("settings"))) ){
			ob_start();
				require_once __DIR__."/settings/index.php";
				$content = ob_get_contents();
			ob_end_clean();
			return $content;
		} else return false;
	}

	public static function url($param){
		switch ($param) {
			case 'settings'	:  return \ENVIRONMENT::url()->root(false).self::$_www_config["credentials"]["url"]; break;
			case 'install'	:  return \ENVIRONMENT::url()->root(false)."/www/install/"; break;
			case 'root' 	:  return \ENVIRONMENT::url()->root(false); break;
			default: return false; break;
		}
	}

}

?>
<?php eWWW::initialize(); ?>
