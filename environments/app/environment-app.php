<?php
namespace maple\environments;

class eApp implements iRenderEnvironment{
	private static $content = null;
	private static $config = null;
	private static $app_config = null;
	private static $active = false;
	private static $_conf_location = __DIR__."/configuration.json";

	public static function initialize(){
		require_once 'class-app.php';
		if(!self::$config){
			self::$config = self::configuration();
			$partial = str_replace(\ENVIRONMENT::url()->root(false),"",\ENVIRONMENT::url()->current());
			foreach (self::$config["optimisation"] as $link => $dir) {
				$link .= self::$config["base"];
				if(strrpos($partial,$link) === 0){
					self::$config["use"]	= $dir;
					self::$config["web-root"]	= $link;
				}
			}
			if(self::$config["use"]){
				self::$app_config = \maple\app\APP::configuration(self::$config["use"])["configuration"];
				self::$active = true;
			}
			\ENVIRONMENT::define("APP",self::$config["base-folder"]."/".self::$config["use"]);
		}
		return self::$active;
	}

	public static function configuration(){
		static $_conf_temp = null;
		if($_conf_temp === null){
			return json_decode(file_get_contents(self::$_conf_location),true);
		} else return $_conf_temp;
	}

	public static function configuration_location(){ return self::$_conf_location;}

	public static function load(){
		self::initialize();
	}
	public static function execute(){
		$content = self::diagnostics();
		if(\ENVIRONMENT::is_allowed("build-server") && self::$active && !$content ){

		}
		if($content) self::$content = $content;
	}

	public static function direct(){
		if(!self::initialize()) return;
		if(\ENVIRONMENT::is_allowed("build-server") && self::$active && strrpos($file, self::$config["web-root"]."/") === 0){
			ob_start();
			$file = str_replace(\ENVIRONMENT::url()->root(false),"",\ENVIRONMENT::url()->current());



			self::$content = ob_get_contents();
			ob_end_clean();
		}
		return true;
	}

	public static function has_content(){ return self::$content?true:false; }

	public static function content(){ return self::$content; }

	public static function error($param){
	}

	public static function diagnostics(){
		$__url = [];
		$__url["%CURRENT%"] = \ENVIRONMENT::url()->current();
		$__url["%APP%"] 	= \ENVIRONMENT::url()->root(false).self::$config["web-root"];
		$page = str_replace($__url["%APP%"], "", $__url["%CURRENT%"]);
		if(!self::$config["use"] && !self::$config["credentials"] && strrpos($page,str_replace(\ENVIRONMENT::url()->root(false).self::$config["web-root"],"",self::url("install"))) === 0 ){
			ob_start();
			require_once __DIR__."/settings/setup/index.php";
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		} else if (self::$config["credentials"] && self::$config["credentials"]["url"] && strrpos($page,str_replace(\ENVIRONMENT::url()->root(false).self::$config["web-root"],"",self::url("settings"))) === 0  ){
			ob_start();
			require_once __DIR__."/settings/index.php";
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		} else return false;
		return false;
	}

	public static function url($param){
		switch ($param) {
			case 'settings'	:  return \ENVIRONMENT::url()->root(false).self::$config["web-root"].self::$config["credentials"]["url"]."/"; break;
			case 'install'	:  return \ENVIRONMENT::url()->root(false).self::$config["web-root"]."/install/"; break;
			case 'root' 	:  return \ENVIRONMENT::url()->root(); break;
			default: return false; break;
		}
	}
}

?>
<?php eApp::initialize(); ?>
