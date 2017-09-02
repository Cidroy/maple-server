<?php
	namespace maple\cms;

	/**
	 * Session handler
	 * @since 1.0
	 * @package Maple CMS
	 * @author Rubixcode
	 */
	class SESSION{

		/**
		 * Request Value for session token
		 * @var string
		 */
		const token_request = "maple-secure-token";

		/**
		 * Token Encryption key
		 * @var string
		 */
		private static $token_encryption_key = "";

		/**
		 * data
		 * @var array
		 */
		private static $_data = [
			"name"	=>	"MAPLE",
			"id"	=>	false,
			"life"	=>	864000
		];

		/**
		 * Start Session
		 * @api
		 * @filter session-started
		 * @throws \RuntimeException if session already active
		 * @throws \InvalidArgumentException if $life not of type 'integer'
		 * @param  integer $life session life in seconds
		 * if false uses default life span
		 */
		public static function start($life=false){
			if (session_status() != PHP_SESSION_NONE) throw new \RuntimeException("Session already active", 1);
			if($life && !is_integer($life)) throw new \InvalidArgumentException("Argument #1 must be type 'integer'", 1);

			self::use_token();
			if(self::$_data["id"]) session_id( self::$_data["id"]);
			$life = $life ? $life : self::$_data["life"];
			session_name(self::$_data["name"]);
			session_set_cookie_params($life);
			session_start();

			self::$_data["id"]	= session_id();
			MAPLE::do_filters("session-started",$filter=[]);
		}

		/**
		 * Use Token as Session
		 */
		private static function use_token(){
			if(isset($_REQUEST[self::token_request]) and $token = $_REQUEST[self::token_request]){
				$token = SECURITY::decrypt(self::$token_encryption_key,$token);
				session_id($token);
			}
		}

		/**
		 * Return session token to be used
		 * @api
		 * @return string token
		 */
		public static function token(){
			if(!self::active()) return false;
			return SECURITY::encrypt(self::$token_encryption_key,session_id());
		}

		/**
		 * Session status
		 * @api
		 * @return boolean status
		 */
		public static function active(){ return session_status() != PHP_SESSION_NONE; }

		/**
		 * Restart Session if not started already
		 * @api
		 * @filter session-started
		 */
		public static function refresh(){
			if(!self::$_data["id"]) self::start();
			MAPLE::do_filters("session-started",$filter=[]);
		}

		/**
		 * Pause current session
		 * @api
		 * @filter session-pausing
		 * @filter session-paused
		 */
		public static function pause(){
			if(!self::active()) return;
			MAPLE::do_filters("session-pausing",$filter=[]);
			session_commit();
			MAPLE::do_filters("session-paused",$filter=[]);
		}

		/**
		 * Extend Sessions life
		 * @api
		 * @param  integer $plus life
		 */
		public static function extend_life($plus){
			self::pause();
			self::start($plus);
		}

		/**
		 * Terminate this session. Purge it!
		 * @api
		 * @filter session-stopping
		 * @filter session-stopped
		 */
		public static function end(){
			MAPLE::do_filters("session-stopping",$filter=[]);
			session_regenerate_id();
			session_destroy();
			MAPLE::do_filters("session-stopped",$filter=[]);
		}

		/**
		 * Set a session variable for use
		 * @api
		 * @throws \DomainException if invalid $options
		 * @param string	$owner 	name of owner
		 * @param string	$var   	name of variable
		 * @param mixed[]	$val   	value to store
		 * @param string	$options
		 * options :
		 * - overwrite
		 * - append
		 * - no-overwrite
		 * @return bool 			true if successfull
		 */
		public static function set($owner,$var,$val,$options="overwrite"){
			if(!isset($_SESSION["storage"])) $_SESSION["storage"] = [];
			if(!isset($_SESSION["storage"][$owner])) $_SESSION["storage"][$owner] = [];
			switch ($options) {
				case 'overwrite':
					$_SESSION["storage"][$owner][$var] = $val;
					return true;
				break;
				case 'no-overwrite':
					if(isset($_SESSION["storage"][$owner][$var])) return false;
					$_SESSION["storage"][$owner][$var] = $val;
					return true;
				break;
				case 'append':
					if(isset($_SESSION["storage"][$owner][$var])){
						if(is_array($_SESSION["storage"][$owner][$var]))
							$_SESSION["storage"][$owner][$var] = array_merge($_SESSION["storage"][$owner][$var],$val);
						else if(is_string($_SESSION["storage"][$owner][$var]))
							$_SESSION["storage"][$owner][$var] = $_SESSION["storage"][$owner][$var].$val;
						else return false;
						return true;
					}
					$_SESSION["storage"][$owner][$var] = $val;
					return true;
				break;
				default:
					throw new \DomainException("Invalid Value for Argument #4", 1);
					return false;
				break;
			}
		}

		/**
		 * Gets the session variables that are set
		 * @api
		 * @param  string  $owner   name of the owner of variable
		 * @param  string  $var     name of variable
		 * @param  boolean $destroy if true then delete the variable after return
		 * @return mixed[] 			the value store in owner:var
		 */
		public static function get($owner,$var,$default = false,$destroy = false){
			if(
				isset($_SESSION["storage"]) &&
				isset($_SESSION["storage"][$owner]) &&
				is_array($_SESSION["storage"][$owner]) &&
				isset($_SESSION["storage"][$owner][$var])
			){
				$ret = $_SESSION["storage"][$owner][$var];
				if($destroy) self::remove($owner,$var);
				return $ret;
			};
			return $default;
		}

		/**
		 * Unset a session variable
		 * @api
		 * @param string $namespace namespace
		 * @param string $var       variable name
		 */
		public static function remove($owner,$var){
			if(
				isset($_SESSION["storage"]) &&
				isset($_SESSION["storage"][$owner]) &&
				is_array($_SESSION["storage"][$owner]) &&
				isset($_SESSION["storage"][$owner][$var])
			)
			unset($_SESSION["storage"][$owner][$var]);
		}
	}

?>
