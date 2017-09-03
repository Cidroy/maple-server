<?php
namespace maple\cms\plugin;
use maple\cms\ERROR;
use maple\cms\UI;
use maple\cms\TEMPLATE;
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
		if(!\maple\cms\SECURITY::permission("maple/cms","dashboard")) return;
		UI::add_filter(__CLASS__."::dashboard_ui_filter");
	}

	public static function f_body_end(){
		return ERROR::show_debug_bar();
	}

	/**
	 * Modify Output Content for Dashboard view
	 * @param  array $context context
	 * @return string          modified content
	 */
	public static function dashboard_ui_filter($context){
		return TEMPLATE::render("maple/theme","page/dashboard",[
			"sidebar" => [
				"admin-messages" => [],
				"menus"	=>	\maple\cms\ADMIN::get_dashboard_menu()
			],
			"context" => $context
		]);
	}
}
?>
