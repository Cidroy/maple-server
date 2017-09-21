<?php
namespace maple\cms;
/**
 * Admin Content Handler
 * @since 1.0
 * @package Maple CMS
 * @subpackage Maple CMS Plugin
 * @author Rubixcode
 */
class ADMIN{

	/**
	 * Dashboard UI contents
	 * @var array
	 */
	private static $_UI = [
		"menus"		=>	null,
		"dashboards"=>	null,
		"widgets"	=>	null,
	];

	/**
	 * Dashboard UI contets buffer
	 * @var array
	 */
	private static $ui = [
		"menus"		=>	[],
		"dashboard"	=>	[],
		"widgets"	=>	[],
	];

	/**
	 * Initialize Dashboard admin helper
	 * @access private
	 */
	public static function initialize(){
		self::$ui = MAPLE::_get("ui");
	}

	/**
	 * Get the Menu list for dashboard
	 * @access private
	 * @return array menu list
	 */
	public static function get_dashboard_menu(){
		if(self::$_UI["menus"]===null){
			foreach (self::$ui["menus"] as $menus ) {
				$namespace = $menus["namespace"];
				if(isset($menus["permission"])){
					if(is_array($menus["permission"]) && !SECURITY::permission(null,$menus["permission"])) continue;
					else if(is_string($menus["permission"]) && !SECURITY::permission($namespace,$menus["permission"])) continue;
				}
				$buffer_menu = [];
				$buffer_menu["heading"] = $menus["name"];
				if(is_array($menus["url"])){
					reset($menus["url"]);
					$menus["url"] = URL::name(key($menus["url"]),current($menus["url"]));
				} else $menus["url"] = URL::http($menus["url"]);
				$buffer_menu["link"] = $menus["url"];
				$buffer_menu["icon"] = $menus["icon"]?$menus["icon"]:"apps";
				$buffer_menu["active"] = URL::matches($menus["url"]);
				if(isset($menus["more"])){
					$buffer_menu["more"] = [];
					foreach ($menus["more"] as $menu) {
						$buffer_sub_menu = [];
						if(isset($menu["permission"])){
							if(is_array($menu["permission"]) && !SECURITY::permission(null,$menu["permission"])) continue;
							else if(is_string($menu["permission"]) && !SECURITY::permission($namespace,$menu["permission"])) continue;
						}
						$buffer_sub_menu = [];
						$buffer_sub_menu["heading"] = $menu["name"];
						if(is_array($menu["url"])){
							reset($menu["url"]);
							$menu["url"] = URL::name(key($menu["url"]),current($menu["url"]));
						} else $menu["url"] = URL::http($menu["url"]);
						$buffer_sub_menu["link"] = $menu["url"];
						$buffer_sub_menu["icon"] = $menu["icon"]?$menu["icon"]:false;
						$buffer_sub_menu["active"] = $menu["url"] && URL::matches($menu["url"]) && ( URL::matches($menu["url"]."/") || URL::http("%CURRENT%")===$menu["url"] );
						$buffer_menu["more"][] = $buffer_sub_menu;
					}
				}
				self::$_UI["menus"][] = $buffer_menu;
			}
		}
		return self::$_UI["menus"];
	}

	/**
	 * Display Dashboard Page
	 * @return string html page
	 */
	public static function p_dashboard(){
		MAPLE::has_content(true);
		if(self::$_UI["dashboards"] === null){
			self::$_UI["dashboards"] = [];
			ob_start();
			foreach (self::$ui["dashboard"] as $dashboard) {
				if(isset($dashboard["permission"]) and !SECURITY::permission(null,$dashboard["permission"])) continue;
				try {
					$res = call_user_func($dashboard["function"],isset($dashboard["arguments"])?$dashboard["arguments"]:null);
					if(is_string($res)) $res = [
						"name"	=>	isset($dashboard["name"])?$dashboard["name"]:md5($dashboard["function"]),
						"content"=> $res
					];
					else if(is_array($res)) $res = array_merge(["name" => isset($dashboard["name"])?$dashboard["name"]:md5($dashboard["function"]) ],$res);
					else if(!$res) continue;
					else throw new \Exception("Unsopported output type for dashboard by function {$dashboard["function"]}", 1);
					self::$_UI["dashboards"][] = $res;
				} catch (\Exception $e) { LOG::error($e); }
			}
			ob_end_clean();
		}
		return TEMPLATE::render("maple/cms","dashboard",[
			"dashboards"	=>	self::$_UI["dashboards"],
		]);
	}
}

ADMIN::initialize();

?>
