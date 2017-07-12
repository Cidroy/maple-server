<?php
/**
 *
 */
class RenderEngine{
	private static $_twig_path = ROOT.INC."/Vendor/Twig/Autoloader.php";
	private static $_twig_template_path = ROOT.DATA."template/";
	private static $_twig_render_cache = ROOT.DATA."template/cache/";
	private static $_twig = null;
	private static $_twig_loader = null;
	private static $_twig_on = false;
	private static $_permissions = [];
	private static $_www_config = false;
	private static $_reder_default_vals = [];
	private static $_template_loaders = [];
	private static $_twig_envs = [];
	private static $_twig_default_source = [];

	protected static $_template_namespaces = [];
	protected static $_template_sources = [];

	public static function Initialize(){
		if(isset($_REQUEST["maple_template_render"]) && $_REQUEST["maple_template_render"]=="false")
			return;
		if(FILE::safe_require_once(self::$_twig_path)){
			self::$_twig_on = true;
			Twig_Autoloader::register();
			self::$_twig_loader = new Twig_Loader_Filesystem(self::$_twig_template_path);
			self::$_twig = new Twig_Environment(self::$_twig_loader, [
				'cache' => self::$_twig_render_cache,
				'debug' => DEBUG,
			]);
			if(DEBUG)	self::$_twig->addExtension(new Twig_Extension_Debug());
		}
	}

	private static function _set_render_defalult(){
		if(!self::$_permissions){
			$temp = SECURITY::get_permissions();
			foreach ($temp as $perm) {
				self::$_permissions[$perm] = true;
			}
		}
		if(!self::$_reder_default_vals){
			self::$_reder_default_vals = [
				"permission"	=>	self::$_permissions,
				"maple"			=>	[
					"url"	=>	[
						'root'		=>	URL::http("%ROOT%"),
						'admin'		=>	URL::http("%ADMIN%"),
						'plugin'	=>	URL::http("%PLUGIN%"),
						'include'	=>	URL::http("%INCLUDE%"),
						'current'	=>	URL::http("%CURRENT%"),
						'data'		=>	URL::http("%DATA%"),
						'theme'		=>	URL::http("%THEME%"),
					],
					"request"	=>	$_REQUEST
				],
			];

		}
	}

	/*
	TODO : smart cache arrays
	 */
	public static function Render($author,$template,$value){
		$ret = false;
		self::_set_render_defalult();
		$value["permission"] = self::$_reder_default_vals["permission"];
		$value["maple"]	= self::$_reder_default_vals["maple"];
		$value["maple"]["request"] = $_REQUEST;
		if(!self::$_twig_on){
			$data = [
				"author"	=>	$author,
				"template"	=>	$template,
				"values"	=>	$value,
			];
			if(isset($_REQUEST["maple_template_show_file"]) && $_REQUEST["maple_template_show_file"]=="true"){
				$file 		 	= URL::dir(self::$_twig_template_path."{$author}/{$template}.html");
				$data["text"]	= file_exists($file)?FILE::read($file):false;
			}
			return $data;
		}
		// TODO : change this
		if( in_array($author,self::$_template_namespaces) ){
			if( !isset(self::$_template_loaders[$author]) ){
				self::$_template_loaders[$author] = new Twig_Loader_Filesystem(array_merge([self::$_template_sources[$author]],self::$_twig_default_source));
				self::$_twig_envs[$author]		  = new Twig_Environment(self::$_template_loaders[$author],[
					"debug"	=>	DEBUG,
					"cache"	=>	self::$_twig_render_cache
				]);
				if(DEBUG)	self::$_twig_envs[$author]->addExtension(new Twig_Extension_Debug());
			}
			$ret = self::$_twig_envs[$author]->render("{$template}.html",$value);
		}
		else if(file_exists(URL::dir(self::$_twig_template_path."{$author}/{$template}.html")) && self::$_twig_on){
			$ret = self::$_twig->render("{$author}/{$template}.html",$value);
		}
		else if(!self::$_twig_on) Log::debug('Twig initialization failure','');
		else Log::debug("Template not found",['author'=>$author,'template'=>$template]);
		return $ret;
	}

	public static function RenderText($text,$value){
		self::_set_render_defalult();
		$value["permission"] = self::$_reder_default_vals["maple"];
		$value["maple"]	= self::$_reder_default_vals["maple"];
		if(!self::$_twig_on){
			$data = [
				"text"		=>	$text,
				"values"	=>	$value,
			];
			return $data;
		}
		$template = self::$_twig->createTemplate($text);
		return $template->render($value);
	}

	public static function RenderFile($file,$value){
		self::_set_render_defalult();
		$value["permission"] = self::$_reder_default_vals["maple"];
		$value["maple"]	= self::$_reder_default_vals["maple"];
		if(!self::$_twig_on){
			$data = [
				"file"		=>	URL::conceal_path($file),
				"values"	=>	$value,
			];
			if(isset($_REQUEST["maple_template_show_file"]) && $_REQUEST["maple_template_show_file"]=="true"){
				$file 		 	= URL::dir(self::$_twig_template_path."{$author}/{$template}.html");
				$data["text"]	= file_exists($file)?FILE::read($file):false;
			}
			return $data;
		}
		if(!file_exists($file)) return "";
		$text = file_get_contents($file);
		$template = self::$_twig->createTemplate($text);
		return $template->render($value);
	}

	public static function add_sources($param){
		self::$_template_sources = array_merge(self::$_template_sources,$param);
		self::$_template_namespaces = array_keys(self::$_template_sources);
	}

	public static function add_default_source($location){
		if($location) self::$_twig_default_source[] = $location;
	}
}
?>
