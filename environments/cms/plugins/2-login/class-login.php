<?php
namespace maple\cms;

/**
 * Login Handler
 * @since 1.0.0
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
	 * Path to configurations file
	 * @var string file path
	 */
	const config_file = \ROOT.\CONFIG."/plugin/maple-login.json";

	/**
	 * Default Configuration
	 * @var array
	 */
	const default_configuration = [
		"registration|allowed"	=>	false,
		"new-user|default-group"=>	0,
	];

	/**
	 * current settings
	 * @var array
	 */
	private static $_settings = [];

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
	 * @filter "login|successfull" on successfull login
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
		if(USER::login($_POST["username"],$_POST["password"])) return MAPLE::do_filters("login|successfull",[
			"type"	=>	"success",
			"user"	=>	[
				"id"	=>	USER::id(),
				"name"	=>	USER::details("name"),
				"username"	=>	USER::details("username"),
			],
			"credentials"	=>	[
				"key"		=>	SESSION::token(),
				"request"	=>	SESSION::token_request
			],
			"redirect_to" => isset($_REQUEST["redirect_to"])?$_REQUEST["redirect_to"]:(SECURITY::permission("maple/cms","dashboard")?URL::name("maple/cms","dashboard"):URL::http("%ROOT%")),
		])->content;
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
		if(!isset($_REQUEST["ajax"])) URL::redirect(URL::http("%ROOT%"));
		return [ "type"	=>	"success", ];
	}

	/**
	 * Default API Handler
	 * @param  mixed $param Parameters
	 * @return array        output
	 */
	public static function a_default($param = ""){
		return $_REQUEST;
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

	/**
	 * Return current configurations
	 * @api
	 * @param  string $param setting name
	 * @return mixed        value
	 */
	public static function settings($param){
		if(!is_string($param)) throw new \InvalidArgumentException( "Argument #1 must be of type 'string'");
		switch ($param) {
			case 'registration|allowed': return self::$_settings["registration|allowed"]; break;
			case 'new-user|default-group': return self::$_settings["new-user|default-group"]; break;
			default: return false; break;
		}
	}

	/**
	 * initialize the system
	 */
	public static function initialize(){
		if(!file_exists(self::config_file)) FILE::write(self::config_file,self::default_configuration);
		self::$_settings = FILE::read(self::config_file,true);
	}
}

LOGIN::initialize();

?>
