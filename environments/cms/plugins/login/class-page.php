<?php
namespace maple\cms\login;
use \maple\cms\TEMPLATE;
use \maple\cms\SECURITY;
use \maple\cms\MAPLE;
use \maple\cms\UI;
use \maple\cms\LOG;

/**
 * Pae Handler
 * @since 1.0
 * @package Maple CMS Login
 * @author Rubixcode
 */
class PAGE{
	/**
	 * View My Profile
	 * BUG : does nothing
	 * @router maple/login:page|profile
	 * @return string html
	 */
	public static function profile_view(){
		MAPLE::has_content(true);
		return "j";
	}

	/**
	 * Forgot Password Page
	 * @router maple/login:page|forgot-password
	 * @return string html
	 */
	public static function forgot_password(){
		MAPLE::has_content(true);
		$errors = [
			[
				"type"		=>	"info",
				"message"	=>	"Please enter your E-Mail address. You will receive a link to create a new password via E-Mail."
			]
		];
		UI::js()->add_src(__DIR__."/assets/index.js");
		return TEMPLATE::render("maple/login","forgot-password",[
			"errors"	=>	$errors,
		]);
	}

	/**
	 * Dashboard Home Page
	 * @return string html
	 */
	public static function d_home(){
		MAPLE::has_content(true);
		return TEMPLATE::render("maple/login","dashboard-home");
	}

}

?>
