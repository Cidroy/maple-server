<?php
namespace maple\cms\plugin;
use maple\cms\ERROR;
/**
 * Maple Handler class
 * @since 1.0
 * @package Maple CMS
 * @subpackage Maple CMS Plugin
 */
class MAPLE{

	/**
	 * Dashboard page Shortcode handler
	 * @param  array  $param shortcode parameters
	 * @return string        content
	 */
	public static function sc_dashboard($param = []){
		return "string";
	}

	public static function f_body_end(){
		return ERROR::show_debug_bar();
	}
}
?>
