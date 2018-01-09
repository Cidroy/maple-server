<?php
	class SESSION{
		private static $_data = [
			"name"	=>	"MAPLE",
			"id"	=>	false,
			"life"	=>	864000
		];

		public static function start($life=false){
			if(isset($_REQUEST['maple_secure_token']) && $_REQUEST['maple_secure_token']){
				session_id($_REQUEST['maple_secure_token']);
			}
			if(self::$_data["id"]) session_id( self::$_data["id"]);
			$life = $life ? $life : self::$_data["life"];
			session_name(self::$_data["name"]);
			session_set_cookie_params($life);
			session_start();

			self::$_data["id"]	= session_id();
		}

		public static function refresh(){
			if(!self::$_data["id"]) self::start();
			MAPLE::do_filters("session_start");
		}

		public static function pause(){
			session_commit();
		}

		public static function extend_life($plus){
			self::pause();
			self::start($plus);
		}

		public static function end(){
			session_regenerate_id();
			session_destroy();
		}

		/**
		 * TODO : provide $options functionality
		 * Set a session variable for use
		 * @param string $owner 	name of owner
		 * @param string $var   	name of variable
		 * @param *		 $val   	value to store
		 * @param int	 $options   options : overwrite , append , no_overwrite
		 * @return bool 			true if successfull
		 */
		public static function set_var($owner,$var,$val,$options=false){
			if(!isset($_SESSION["storage"])) $_SESSION["storage"] = [];
			if(!isset($_SESSION["storage"][$owner])) $_SESSION["storage"][$owner] = [];
			$_SESSION["storage"][$owner][$var] = $val;
		}

		/**
		 * Gets the session variables that are set
		 * @param  string  $owner   name of the owner of variable
		 * @param  string  $var     name of variable
		 * @param  boolean $destroy if true then delete the variable after return
		 * @return *           		the value store in owner:var
		 */
		public static function get_var($owner,$var,$destroy = false,$default = false){
			if(
				isset($_SESSION["storage"]) &&
				isset($_SESSION["storage"][$owner]) &&
				is_array($_SESSION["storage"][$owner]) &&
				isset($_SESSION["storage"][$owner][$var])
			){
				$ret = $_SESSION["storage"][$owner][$var];
				if($destroy) unset($_SESSION["storage"][$owner][$var]);
				return $ret;
			};
			return $default;
		}
	}

?>
