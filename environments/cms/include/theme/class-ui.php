<?php
namespace maple\cms;
use maple\cms\ui\components\__navbar;
use maple\cms\ui\components\__links;
use maple\cms\ui\components\__html;
use maple\cms\ui\components\__title;
require_once 'classes-ui-components.php';
require_once 'interface-ui.php';

/**
 * UI Commands
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class UI implements iUI{
	private static $objs = [];
	private static $theme = null;

	/**
	 * Initialize
	 * @uses \maple\cms\THEME::theme_class
	 */
	public static function initialize(){
		self::$objs["navbar"] = new __navbar();
		self::$objs["css"] = new __links();
		self::$objs["js"] = new __links();
		self::$objs["header"] = new __html();
		self::$objs["footer"] = new __html();
		self::$objs["title"] = new __title();

		self::$theme = THEME::theme_class();
	}

	public static function navbar() { return self::$objs["navbar"]; }
	public static function css(){ return self::$objs["css"]; }
	public static function js(){ return self::$objs["js"]; }
	public static function header() { return self::$objs["header"]; }
	public static function footer() { return self::$objs["footer"]; }
	public static function title() { return self::$objs["title"]; }

	/**
	 * Return html icon tag for icon
	 * @api
	 * @throws \InvalidArgumentException if $name not of type 'string'
	 * @param  string $name name
	 * @return string       html
	 */
	public static function icon($name){
		if(!self::$theme) return false;
		return call_user_func(self::$theme."::icon",$name);
	}
}

UI::initialize();
?>
