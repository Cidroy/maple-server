<?php
namespace maple\cms;

/**
 * Login Handler
 * @since 1.0
 * @package Maple CMS Login
 * @author Rubixcode
 */
class LOGIN{

	/**
	 * App namespace
	 * @var string
	 */
	const app_namespace = "maple/login";

	/**
	 * Errors List
	 * @var array
	 */
	const error = [
		"insufficient-parameters" => [0,"Insufficient Details Given, Please fill all the required details."],
		"invalid-credentials" => [1,"Invalid Username of Password"]
	];

	/**
	 * Add Navbar Elements
	 * @filter-handler pre-render|head
	 * @filter user|navbar-actions
	 */
	public static function f_add_navbar_elements(){
		if(USER::loggedin()){
			if(SECURITY::permission("maple/cms","dashboard") && URL::http("%CURRENT%")!=URL::http("%ADMIN%"))
				UI::navbar()->add_link("Dashboard",URL::name("maple/cms","dashboard"),"dashboard");
			#TODO : change to add_html -> template dropdown with actions
			UI::navbar()->add_link(USER::details("name"),URL::name("maple/login","profile|view"),"people");
		} else {
			if(SECURITY::permission("maple/login","login") && URL::http("%CURRENT%")!=URL::name("maple/login","page|login"))
				UI::navbar()->add_link("Login",URL::name("maple/login","page|login")."/?redirect_to=".URL::http("%CURRENT%"),"login");
			if(SECURITY::permission("maple/login","sign-up") && URL::http("%CURRENT%")==URL::name("maple/login","page|login"))
				UI::navbar()->add_link("Register",URL::name("maple/login","page|sign-up"),"login");
		}
	}

	/**
	 * @api-handler "maple/login:login"
	 * @response success array {
	 *           string "type" : "success",
	 *           array 	"user" : {
	 *           	string "name" : name,
	 *           	string "username" : username,
	 *           	string "access" : access,
	 *           	string "access-code" : access-code,
	 *           	array "permissions" ,
	 *           }
	 * }
	 * @response error array {
	 *           string "type" : "error",
	 *           string "message" : message
	 *           string "code" : code
	 * }
	 * @return array data
	 */
	public static function a_login(){
		if(!URL::has_request(["username","password"])) return self::_error("insufficient-parameters");
		if(USER::login($_POST["username"],$_POST["password"])) return [
			"type"	=>	"success",
			"user"	=>	[
				"id"	=>	USER::id(),
				"name"	=>	USER::details("name"),
			],
			"credentials"	=>	[
				"key"		=>	SESSION::token(),
				"request"	=>	SESSION::token_request
			],
			"redirect_to" => isset($_REQUEST["redirect_to"])?$_REQUEST["redirect_to"]:(SECURITY::permission("maple/cms","dashboard")?URL::name("maple/cms","dashboard"):URL::http("%ROOT%")),
		];
		else return self::_error("invalid-credentials");
	}

	/**
	 * @api-handler "maple/login:logout"
	 * @response success array {
	 *           "type" : "success"
	 * }
	 * @response error array {
	 *           "type"	: "error",
	 *           "message" : message,
	 *           "code" : code,
	 * }
	 */
	public static function a_logout(){
		USER::logout();
		return [ "type"	=>	"success", ];
	}

	/**
	 * return error
	 * @param  string $error error name
	 * @return array        return error array
	 */
	private static function _error($error){
		return [
			"type"		=>	"error",
			"code"		=>	self::error[$error][0],
			"message"	=>	LANGUAGE::translate(self::app_namespace,self::error[$error][1]),
		];
	}
}

?>
