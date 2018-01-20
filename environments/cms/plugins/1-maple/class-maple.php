<?php
namespace maple\cms\plugin;
use maple\cms\ERROR;
use maple\cms\ADMIN;
use maple\cms\UI;
use maple\cms\URL;
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

	public static function f_body_start(){
		UI::js()->add("const ROOT = '".URL::http("%API%")."';");
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

	public static function a_plugin(){
		try {
			$detail = \maple\cms\PLUGIN::details($_REQUEST["plugin"]);
			$status = false;
			$notify = [];
			switch ($_REQUEST["action"]) {
				case 'reload':
					$status = \maple\cms\PLUGIN::deactivate($_REQUEST["plugin"]);
					$status = \maple\cms\PLUGIN::activate($_REQUEST["plugin"]);
					$notify = [
						"message"	=>	$status?"Plugin {$detail["name"]} - {$detail["version"]} Reloaded":"Plugin {$detail["name"]} - {$detail["version"]} Reload Failed",
					];
				break;
				case 'activate':
					$status = \maple\cms\PLUGIN::activate($_REQUEST["plugin"]);
					$notify = [
						"message"	=>	$status?"Plugin {$detail["name"]} - {$detail["version"]} Activated":"Plugin {$detail["name"]} - {$detail["version"]} Activation Failed",
					];
				break;
				case 'deactivate':
					$status = \maple\cms\PLUGIN::deactivate($_REQUEST["plugin"]);
					$notify = [
						"message"	=>	$status?"Plugin {$detail["name"]} - {$detail["version"]} Deactivated":"Plugin {$detail["name"]} - {$detail["version"]} Deactivation Failed",
					];
				break;
				default: break;
			}
		} catch (\Exception $e) {
			$notify = [
				"message"	=>	"Plugin {$detail["name"]} - {$detail["version"]} action {$_REQUEST["action"]} Failed <br> {$e->getMessage()}"
			];
		}
		ADMIN::notify($notify);
		URL::redirect($_REQUEST["redirect_to"]);
		return $status;
	}
}
?>
