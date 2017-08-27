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
		$sc_login = new SHORTCODE("login",["page"=>"default"]);
		$sc_register = new SHORTCODE("register",["page"=>"default"]);
		PAGE::add([
			"name"	=>	"login",
			"url"	=>	"/login",
			"title"	=>	"Login",
			"content"=> (string)$sc_login
		]);
		PAGE::add([
			"name"	=>	"register",
			"url"	=>	"/sign-up",
			"title"	=>	"Sign-Up",
			"content"=> (string)$sc_register
		]);
	}
}

?>
