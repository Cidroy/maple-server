<?php
namespace maple\cms;
/**
 * User class
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class USER{
	const user_detail_template = [
		"id"	=>	false,
		"name"	=>	false,
		"username"=>	false,
		"email"	=>	false,
		"access"=>	false,
		"permissions"=>	[
			"grant"	=>	[],
			"deny"	=>	[],
			"update"=> 0
		],
		"details"=>	[
			"access"	=>	false,
			"permissions"=>	[]
		]
	];
	/**
	 * Stores initialization status
	 * @var boolean
	 */
	private static $_initialized = false;
	/**
	 * Stores user details
	 * @var array
	 */
	private static $_user = [];

	/**
	 * Run Diagnostics
	 */
	private static function diagnose(){}
	/**
	 * Assign user to current session
	 * @throws \InvalidArgumentException id $id is not of type 'integer'
	 * @param integer $id user id
	 */
	private static function _set($id){
		if(!is_integer($id)) throw new \InvalidArgumentException("Argument #1 should be of type 'integer'", 1);
		SESSION::set("maple/cms","user",["id" => $id]);
	}
	/**
	 * Unset user from current session
	 */
	private static function _unset(){ SESSION::remove("maple/cms","user"); }

	/**
	 * initialize user
	 * @api
	 * @throws \RuntimeException if session not active
	 * @filter user|initialized {
	 *         When user is initialized
	 *         @type integer 'id'
	 * }
	 */
	public static function initialize(){
		if(!SESSION::active()) throw new \RuntimeException("'\\maple\\cms\\SESSION' not active", 1);
		$session = SESSION::get("maple/cms","user");
		if($session) self::$_user["id"] = $session["id"];
		else self::$_user["id"]	= false;
		$data = [];
		if(self::$_user["id"]!==false){
			$data = DB::_()->select("users","*",[ "id" => self::$_user["id"] ]);
			$data = current($data);
			$data["permissions"] = json_decode($data["permissions"],true);
			self::$_user["details"] = array_merge(self::user_detail_template,$data);
		} else { self::$_user["details"] = self::user_detail_template; }
		self::$_initialized = true;
		MAPLE::do_filters("user|initialized",["id" => self::$_user["id"]]);
	}
	/**
	 * return initialization status
	 * @api
	 * @return boolean status
	 */
	public static function initialized(){ return self::$_initialized; }

	/**
	 * login user
	 * @api
	 * @filter user|loggedin {
	 *         When user has loggedin successfully
	 *         @type integer 'id'
	 * }
	 * @filter user|login-failed when credentials are invalid
	 * @throws \InvalidArgumentException if $username or $password not of type 'string'
	 * @param  string $username username|email
	 * @param  string $password plain text password
	 * @return boolean           status
	 */
	public static function login($username,$password){
		if(!is_string($username)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($password)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		$password = md5($password);
		$data = DB::_()->select("users","*",[
			"AND"	=>	[
				"OR"	=>	[
					"username"	=>	$username,
					"email"	=>	$username,
				],
				"password"	=>	$password,
			]
		]);

		$data = current($data);
		if(count($data)){
			self::_set(intval($data["id"]));
			MAPLE::do_filters("user|loggedin",["id" => $data["id"]]);
			self::initialize();
			return true;
		}
		MAPLE::do_filters("user|login-failed");
		SECURITY::get_permissions(true);
		return false;
	}
	/**
	 * Logout
	 * @api
	 * @filter user|logout when user is about to logout
	 */
	public static function logout(){
		MAPLE::do_filters("user|logout");
		self::_unset();
	}

	/**
	 * Get user id
	 * @api
	 * @return integer id
	 */
	public static function id(){ return self::$_user["id"]; }
	/**
	 * Return Access level
	 * @api
	 * @return integer access level
	 */
	public static function access_level(){ return self::$_user["details"]["access"]; }
	/**
	 * return special permissions
	 * @api
	 * @return array permissions
	 */
	public static function permissions(){ return self::$_user["details"]["permissions"]; }
	/**
	 * return user details
	 * @api
	 * @param  string $attr attribute name
	 * @return mixed[]       value
	 */
	public static function details($attr){ isset(self::$_user["details"][$attr])?self::$_user["details"][$attr]:false; }

	/**
	 * Return Login Status
	 * @api
	 * @return boolean login status
	 */
	public static function loggedin() { return self::id()!==false; }
}

?>
