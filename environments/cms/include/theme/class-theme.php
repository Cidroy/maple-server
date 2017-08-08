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
	 * Does optimisation
	 */
	private static function optimize($param=null){
		if(!file_exists(self::cache) && !is_dir(self::cache)) mkdir(self::cache,0777,true);
		if(!file_exists(self::cache."/installed.json") || ($param && is_array($param))){
			$themes = $param?$param:[];
			foreach (self::$_sources as $source) {
				$theme[$source] = [];
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
		if(!file_exists(self::cache)) self::optimize();
		// BUG: use cache theme based on sources
		$data = json_decode(file_get_contents(self::cache."/installed.json"));
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
			$n = new NOTIFICATION("maple/cms");
			$n->title = "Theme '{$theme}' could not be found switched to '{$theme_alt}'";
			$n->text = "Searched in locations \n".implode("\n",self::$_sources);
			$n->notify();
			return ;
		}
		require_once self::$_details["location"];
	}
	/**
	 * Color etc.
	 * BUG : does nothing
	 * @api
	 * @return array additional theme based render data
	 */
	public static function rendering_data() { }

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
	 * BUG: no data provided
	 * @api
	 * @return string html
	 */
	public static function render_head(){
		if(!self::$_details) return "";
		return call_user_func(self::$_details["class"]."::render_head",[]);
	}

	/**
	 * Render content body
	 * BUG: no data provided
	 * @api
	 * @return string html
	 */
	public static function render_content(){
		if(!self::$_details) return "";
		return call_user_func(self::$_details["class"]."::render_content",[]);
	}

	/**
	 * Render Content Footer
	 * BUG: no data provided
	 * @api
	 * @return string html
	 */
	public static function render_footer(){
		if(!self::$_details) return "";
		return call_user_func(self::$_details["class"]."::render_footer",[]);
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
