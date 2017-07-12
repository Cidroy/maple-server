<?php
namespace maple\environments;

/**
 * Bootup Class for Maple Environment
 * @package Maple Environment
 * @since 1.0
 * @author Rubixcode
 */
class BOOT{
	/**
	 * Location of maple environment configration file
	 * @var file path
	 */
	const _config_location = \ENVIRONMENT."/environments.json";
	/**
	 * Environment configration
	 * @var array
	 */
	private static $config = [];

	public static function initialize(){
		self::$config = file_get_contents(self::_config_location);
		self::$config = json_decode(self::$config,true);
	}

	/**
	 * test if this is first initialization based on the maple environment credentials
	 * @return boolean status
	 */
	public static function is_first(){
		return !isset(self::$config["settings"]);
	}

	/**
	 * save maple environment credentials
	 * @throws InvalidArgumentException if $param does not contain 'username','password' or 'url'
	 * @throws \maple\environment\excetions\UrlAlreadyRegisteredException if $param["url"] is already registered
	 * @param  array $param credentials details
	 * accepts the following
	 * - string url : url to settings
	 * - string username : usernmame
	 * - string password : password
	 * - bool encrypt : if set false, the password is treated as pre encrypted
	 */
	public static function save_credentials($param){
		if(!isset($param["username"]) || !isset($param["password"])  || !isset($param["url"]) )
			throw new \InvalidArgumentException("Argument #1 must contain the following keys 'username','password' and 'url'", 1);
		$param["url"] = str_replace(\ENVIRONMENT::url()->root(false),"",$param["url"]);
		if($param["url"][0] != "/") $param["url"] = "/".$param["url"];
		if(!\ENVIRONMENT::url()->available("maple/environment",$param["url"]))
			throw new \maple\environment\exceptions\UrlAlreadyRegisteredException($param["url"], 1);
		$_prev_url = isset(self::$config["settings"]["url"])?self::$config["settings"]["url"]:null;
		self::$config["settings"] = [
			"url"		=>	$param["url"],
			"username" 	=>	$param["username"],
			"password"	=>	md5($param["password"]),
			"updated"	=>	time()
		];
		if(isset($param["encrypt"]) && !$param["encrypt"]) self::$config["settings"]["password"] = $param["password"];
		\ENVIRONMENT::lock("maple/environment : save-credentials");
			file_put_contents(self::_config_location,json_encode(self::$config,JSON_PRETTY_PRINT));
			if($_prev_url!==null) \ENVIRONMENT::url()->unregister("maple/environment",$_prev_url);
			\ENVIRONMENT::url()->register("maple/environment",self::$config["settings"]["url"]);
		\ENVIRONMENT::unlock();
	}

	/**
	 * login to maple environment control panel
	 * @throws InvalidArgumentException if $param does not contain 'username','password'
	 * @param  array $param contains credentials and must contain
	 * - string username
	 * - string password
	 * @return boolean true if successfull , false if invalid credentials
	 */
	public static function login($param){
		if(!isset($param["username"]) || !isset($param["password"]))
			throw new \InvalidArgumentException("Argument #1 must contain 'username' and 'password'", 1);
		if(
			$param["username"] == self::$config["settings"]["username"] &&
			md5($param["password"]) == self::$config["settings"]["password"]
		){
			if (session_status() == PHP_SESSION_NONE) session_start();
			$_SESSION["maple/environment"] = [
				"active"	=>	true,
				"login"		=>	[
					"update"	=>	self::$config["settings"]["updated"],
					"begin"		=>	time(),
					"last-active"=> time(),
				]
			];
			return true;
		}
		else return false;
	}

	/**
	 * return if the user is logged in.
	 * updates the last active status
	 * @return boolean status
	 */
	public static function loggedin(){
		try {
			$_session_start = false;
			if (session_status() == PHP_SESSION_NONE){
				$_session_start = true;
				session_start();
			}
			if( isset($_SESSION["maple/environment"]) && $_SESSION["maple/environment"]["active"]){
				if($_SESSION["maple/environment"]["login"]["update"] == self::$config["settings"]["updated"]){
					$_SESSION["maple/environment"]["login"]["last-active"] 	= time();
					if($_session_start) session_commit();
					return true;
				}
				else unset($_SESSION["maple/environment"]);
			}
		} catch (Exception $e) { }
		if($_session_start) session_commit();
		return false;
	}
}
BOOT::initialize();
?>
