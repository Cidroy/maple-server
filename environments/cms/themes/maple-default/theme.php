<?php
namespace maple\theme;

/**
 * Default Theme
 * @since 1.0
 * @package Maple CMS
 * @subpackage Maple CMS Theme - Defalut
 * @author Rubixcode
 */
class DEFAULT_THEME implements \maple\cms\iTheme{
	const palette_list = ["primary","secondary"];

	public static function palette($color){
	}

	public static function palettes(){
	}

	public static function render_head($content){
	}

	public static function render_content($content){
	}

	public static function render_footer($content){
	}

	public static function render_error($e){
	}
}
?>
