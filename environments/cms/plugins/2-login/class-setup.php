<?php
namespace maple\cms\login;
use \maple\cms\SHORTCODE;
use \maple\cms\PAGE;
/**
 * Login Setup Class
 * @since 1.0
 * @package Maple CMS Login
 * @author Rubixcode
 */
class SETUP {
	public static function install(){
		$shortcodes = [
			"login"	=>	new SHORTCODE("login",["page"=>"default"]),
			"register"	=> new SHORTCODE("register",["page"=>"default"]),
			"profile"	=> new SHORTCODE("profile",["page"=>"default"]),
		];
		$pages = [
			[
				"name"	=>	"login",
				"url"	=>	"/login",
				"title"	=>	"Login",
				"content"=> (string)$shortcodes["login"]
			],
			[
				"name"	=>	"register",
				"url"	=>	"/sign-up",
				"title"	=>	"Sign-Up",
				"content"=> (string)$shortcodes["register"]
			],
			[
				"name"	=>	"profile",
				"url"	=>	"/profile",
				"title"	=>	"Profile",
				"content"=> (string)$shortcodes["profile"]
			]
		];
		foreach ($pages as $page ) PAGE::add($page);
	}
}

?>
