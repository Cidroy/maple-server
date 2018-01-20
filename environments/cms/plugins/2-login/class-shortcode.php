<?php
namespace maple\cms\login;
use \maple\cms\TEMPLATE;
use \maple\cms\MAPLE;
use \maple\cms\UI;
use \maple\cms\URL;
use \maple\cms\SECURITY;
/**
 * Shortcode handler class
 * @since 1.0
 * @package Maple CMS Login
 * @author Rubixcode
 */
class SHORTCODE{
	/**
	 * Shorcode Handler : Login
	 * @param  string $content content
	 * @param  array  $param   parameters
	 * Available Options :
	 * - string "page"
	 * 		"default"	=>	Full Page Login
	 * @return strig          html
	 */
	public static function login($content = "",$param = []){
		if(!SECURITY::permission("maple/login","login")){
			URL::redirect(URL::name("maple/login","profile|view"));
			return;
		}
		$errors = [ ];
		UI::js()->add_src(__DIR__."/assets/index.js");
		if(isset($param["page"])) switch ($param["page"]) {
			case 'default':
					MAPLE::has_content(true);
					return TEMPLATE::render("maple/login","login-page-full",[
						"content"	=>	$content,
						"param"		=>	$param,
						"errors"	=>	$errors,
					]);
				break;
			default:
					return "";
				break;
		}
	}

	public static function profile(){
		UI::add_filter(__CLASS__."::dashboard_ui_filter");
	}

	public static function dashboard_ui_filter($context){
		return TEMPLATE::render("maple/theme","page/dashboard",[
			"sidebar"	=>	[
				"menus"	=>	self::menus()
			],
			"context"	=>	$context,
		]);
	}

	private static function menus(){
		return [
			[
				"link"	=>	URL::name("maple/login","profile|view"),
				"heading"=>	"View",
				"icon"	=>	"home",
			],
			[
				"link"	=>	URL::ajax("maple/login","logout"),
				"heading"=>	"Logout",
				"icon"	=>	"apps",
			],
		];
	}
}

?>
