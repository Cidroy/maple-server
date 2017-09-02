<?php
namespace maple\cms;

require_once \ROOT.\INC."/theme/interface-theme.php";
/**
 * Theme Class
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class THEME {
	const default_rendering_data = [
		"background"	=>	"white",
		"color"			=>	"black",
		"accent"		=>	"indigo",
		"palette"		=>	"indigo",
	];

	const cache = \ROOT.\CACHE."/theme";

	/**
	 * all theme loading sources
	 * @var array
	 */
	private static $_sources = [];

	/**
	 * Theme related details
	 * @var array
	 */
	private static $_details = [];

	/**
	 * Theme Render Options
	 * @var array
	 */
	private static $options  = [
		"title-splitter"	=>	" - "
	];

	private static $_initialized = false;

	/**
	 * Does optimisation
	 */
	private static function optimize($param=null){
		if(!file_exists(self::cache) && !is_dir(self::cache)) mkdir(self::cache,0777,true);
		if(!file_exists(self::cache."/installed.json") || ($param && is_array($param))){
			$themes = $param?$param:[];
			foreach (self::$_sources as $source) {
				$themes[$source] = [];
				foreach (FILE::get_folders($source) as $theme) {
					if(!file_exists($theme."/package.json")) continue;
					$data = json_decode(file_get_contents($theme."/package.json"),true);
					if(!is_array($data) || !isset($data["namespace"]) || !isset($data["maple"]["maple/cms"]["theme"]) || !$data["maple"]["maple/cms"]["theme"]) continue;
					// BUG : does not do version compatibility check
					// if(isset($themes[$data["namespace"]])){ }
					$themes[$source][$data["namespace"]] = [
						"version" 	=>	isset($data["version"])?$data["version"]:"1.0.0",
						"location"	=>	$theme,
						"class"		=>	$data["maple"]["maple/cms"]["theme"],
					];
				}
			}
			file_put_contents(self::cache."/installed.json",json_encode($themes));
		}
	}

	/**
	 * Initialize Theme
	 */
	public static function initialize() {
		if(self::$_initialized) return;
		if(!file_exists(self::cache)) self::optimize();
		// BUG: use cache theme based on sources
		$data = json_decode(file_get_contents(self::cache."/installed.json"),true);
		$theme = current(DB::_()->select("options","value",["name" => "theme"]));
		foreach ($data as $source => $themes) {
			if(in_array($source,self::$_sources) && array_key_exists($theme,$themes)){
				self::$_details = $themes[$theme];
				break;
			}
		}
		$theme_alt = "none";
		if(!self::$_details || !file_exists(self::$_details["location"])){
			foreach ($data as $source => $themes) {
				if (current($themes)) {
					$theme_alt = key(current($themes));
					self::$_details = $themes[$theme_alt];
					break;
				}
			}
		}
		if(!self::$_details) {
			Log::error([
				"name"	=>	"no themes found",
				"sources"=>	self::$_sources,
				"theme"	=>	$theme
			]);
			try{
				if(SECURITY::permission("maple/theme","theme|change")){
					$n = new NOTIFICATION("maple/cms");
					$n->title = "Theme '{$theme}' could not be found switched to '{$theme_alt}'";
					$n->text = "Searched in locations \n".implode("\n",self::$_sources);
					$n->notify();
				}
			}catch(\Exception $e){}
			return ;
		}
		TEMPLATE::add_template_sources([ "maple/theme" => self::$_details["location"] ]);
		TEMPLATE::add_default_template_source(self::$_details["location"]);
		require_once self::$_details["location"]."/theme.php";
		call_user_func(self::$_details["class"]."::initialize");
		self::$_initialized = true;
	}
	/**
	 * Color etc.
	 * BUG : does nothing
	 * @api
	 * @return array additional theme based render data
	 */
	public static function rendering_data(){
		$color = call_user_func(self::$_details["class"]."::color");
		return [
			"color"	=>	$color,
		];
	}

	/**
	 * Add Themes Source
	 * @api
	 * @throws \InvalidArgumentException if $source not og type 'string'
	 * @param file-path $source source to theme folder
	 */
	public static function source($source){
		if(!is_string($source)) throw new \InvalidArgumentException("Argument #1 must be of type string", 1);
		if(file_exists($source) && is_dir($source) && !in_array($source, self::$_sources))
			self::$_sources[] = $source;
	}

	/**
	 * Return sources
	 * @return array sources
	 */
	public static function sources() { return self::$_sources; }

	/**
	 * Install new theme
	 * BUG: does nothing
	 * @api
	 * @filter theme|install
	 * @permission maple/theme:theme|install
	 * @throws \InvalidArgumentException if $theme or $market is not of type 'string'
	 * @param  string $theme  namespace
	 * @param  string $market market url
	 * @return boolean         status
	 */
	public static function install($theme,$market = null){}

	/**
	 * Uninstall a theme
	 * BUG: does nothing
	 * @api
	 * @filter theme|uninstall
	 * @permission maple/theme:theme|uninstall
	 * @throws \InvalidArgumentException if $theme is not of type 'string'
	 * @param  string $theme  namespace
	 * @return boolean         status
	 */
	public static function uninstall($theme){}

	/**
	 * Theme get palette
	 * @api
	 * @param  string $color primary
	 * @return array        name => #color
	 */
	public static function palette($color){
		if(!self::$_details) return "";
		return call_user_func(self::$_details["class"]."::palette",$color);
	}

	/**
	 * Get List of color palettes and thier color
	 * @api
	 * @return array name => #color
	 */
	public static function palettes(){
		if(!self::$_details) return "";
		return call_user_func(self::$_details["class"]."::palettes");
	}

	/**
	 * Render content head
	 * @api
	 * @filter pre-render|head
	 * @return string html
	 */
	public static function render_head(){
		if(!self::$_details) return "";
		MAPLE::do_filters("pre-render|head",$filter=[]);
		return call_user_func(self::$_details["class"]."::render_head",[
			"html"	=>	[
				"header"=>	UI::header()->get(),
				"css"	=>	UI::css()->src(),
				"css-script"	=>	UI::css()->get_script(),
			],
			"title"	=>	implode(self::$options["title-splitter"],UI::title()->get()),
			"navbar"=>	[
				"buttons"	=>	UI::navbar()->buttons(),
				"links"		=>	UI::navbar()->links(),
				"html"		=>	UI::navbar()->html(),
			]
		]);
	}

	/**
	 * Render content body
	 * @api
	 * @filter pre-render|content
	 * @return string html
	 */
	public static function render_content(){
		if(!self::$_details) return "";
		MAPLE::do_filters("pre-render|content",$filter=[]);
		return call_user_func(self::$_details["class"]."::render_content",[
			"content"	=>	MAPLE::do_hooks()
		]);
	}

	/**
	 * Render Content Footer
	 * @api
	 * @filter pre-render|head
	 * @return string html
	 */
	public static function render_footer(){
		if(!self::$_details) return "";
		MAPLE::do_filters("pre-render|footer",$filter=[]);
		return call_user_func(self::$_details["class"]."::render_footer",[
			"html"	=>	[
				"footer"=>	UI::footer()->get(),
				"js"	=>	UI::js()->src(),
				"js-script"	=>	UI::js()->get_script(),
			],
		]);
	}

	/**
	 * Render Error
	 * @param  integer $e error code
	 * @return string    html body
	 */
	public static function render_error($e){
		if(!self::$_details) return "";
		return call_user_func(self::$_details["class"]."::render_error",$e);
	}

	/**
	 * Return Class Name for current theme
	 * @return string class name
	 */
	public static function theme_class(){
		if(!self::$_details) return "";
		return self::$_details["class"];
	}

}

?>
