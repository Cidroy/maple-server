<?php
require_once 'classes-ui-components.php';
use maple\ui\components\__navbar;
use maple\ui\components\__JS;
use maple\ui\components\__CSS;
use maple\ui\components\__footer;
use maple\ui\components\__header;
use maple\ui\components\__language;
use maple\ui\components\__content;
use maple\ui\components\__search;
use maple\ui\components\__title;

class UI{
	private static $_content = [
		"footer"	=>	"",
		"header"	=>	"",
		"files"		=>	[
			"js"		=>	[],
			"css"		=>	[],
		],
	];
	private static $objs = [ ];

	public static function initialize(){
		self::$objs["navbar"]	= new __navbar();
		self::$objs["js"]	= new __JS();
		self::$objs["css"]	= new __CSS();
		self::$objs["header"]	= new __header();
		self::$objs["footer"]	= new __footer();
		self::$objs["language"]	= new __language();
		self::$objs["content"]	= new __content();
		self::$objs["search"]	= new __search();
		self::$objs["title"]	= new __title();
	}

	public static function add_footer($script){ self::$_content["footer"].=$script; }
	public static function add_header($script){ self::$_content["header"].=$script; }

	public static function js(){ return self::$objs["js"]; }
	public static function css(){ return self::$objs["css"]; }
	public static function navbar() { return self::$objs["navbar"]; }
	public static function header() { return self::$objs["header"]; }
	public static function footer() { return self::$objs["footer"]; }
	public static function language() { return self::$objs["language"]; }
	public static function content() { return self::$objs["content"]; }
	public static function title() { return self::$objs["title"]; }
	public static function search($args = null) {
		if($args === null) return self::$objs["search"];
		else return self::$onjs["search"]->search($args);
	}

}
UI::initialize();
?>
