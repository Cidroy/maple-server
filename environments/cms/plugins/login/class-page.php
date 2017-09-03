<?php
namespace maple\cms\login;
use \maple\cms\TEMPLATE;
use \maple\cms\SECURITY;
use \maple\cms\MAPLE;
use \maple\cms\UI;
use \maple\cms\URL;
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
		$quick_actions = [
			[
				"title"	=>	"Add User",
				"icon"	=>	"user-add",
				"description"	=>	"Add a new User with a role",
				"link"	=>	URL::name("maple/login/dashboard","users|add"),
				"permissions"	=>	["maple/login"=>"user|add"]
			],
			[
				"title"	=>	"Add User Group",
				"icon"	=>	"group-add",
				"description"	=>	"Add a User Group and define permissions",
				"link"	=>	URL::name("maple/login/dashboard","users-group|add"),
				"permissions"	=>	["maple/login"=>"user-group|add"]
			],
			[
				"title"	=>	"Settings",
				"icon"	=>	"settings",
				"description"	=>	"General Settings",
				"link"	=>	URL::name("maple/login/dashboard","settings"),
				"permissions"	=>	["maple/cms"=>"dashboard"]
			],
		];

		return TEMPLATE::render("maple/login","dashboard-home",[
			"image"	=>	[
				"cover"	=> URL::http(__DIR__."/assets/images/dashboard-cover.jpg",[ "maple-image"	=>	"optimise", "optimise"		=>	"auto" ])
			],
			"quick_actions"	=>	$quick_actions
		]);
	}

}

?>
