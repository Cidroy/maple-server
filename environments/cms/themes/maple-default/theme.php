<?php
namespace maple\theme;
use \maple\cms\TEMPLATE;
use \maple\cms\URL;
use \maple\cms\UI;
/**
 * Default Theme
 * @since 1.0
 * @package Maple CMS
 * @subpackage Maple CMS Theme - Defalut
 * @author Rubixcode
 */
class DEFAULT_THEME implements \maple\cms\iTheme{
	public static function palette($color){
	}

	public static function palettes(){
	}

	public static function initialize(){
		$link = [
			"js"	=>	[
				__DIR__."/js/jquery.min.js",
				__DIR__."/js/materialize.min.js",
			],
			"css"	=>	[
				__DIR__."/css/materialize.min.css",
				__DIR__."/css/style.css",
				__DIR__."/fonts/icons/material-icons.css",
			],
		];
		foreach ($link["js"] as $js ) { UI::js()->add_src($js); }
		foreach ($link["css"] as $css ) { UI::css()->add_src($css); }
	}

	public static function render_head($content){
		return TEMPLATE::render("maple/theme","page/head",$content);
	}

	public static function render_content($content){
		return TEMPLATE::render("maple/theme","page/content",$content);
	}

	public static function render_footer($content){
		return TEMPLATE::render("maple/theme","page/footer",$content);
	}

	public static function render_error($e){
		return TEMPLATE::render("maple/theme","page/error",$e);
	}

	public static function icon($name,$class = false){
		$icon = [
			"login"	=>	"person_pin",
			"person"	=>	"perm_identity",
		];
		$name = isset($icon[$name])?$icon[$name]:$name;
		$name = "<i class=\"material-icons {$class}\">{$name}</i>";
		return $name;
	}

	public static function color(){
		return [
			"footer"	=>	"grey darken-4",
			"navbar"	=>	"indigo darken-4",
			"primary"	=>	"indigo",
		];
	}
}
?>
