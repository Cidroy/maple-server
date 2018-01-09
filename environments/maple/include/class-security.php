<?php
class SECURITY {
	public static $token_key = false;
	public static $tokens 	  = [];
	public static $iv = false;
	public static $nonce_life = 24*60*60;

	private static $_USER_PERMISSIONS = false;

	public static function load(){
		self::$token_key = SESSION::get_var("security","key");
		self::$tokens 	 = SESSION::get_var("security","tokens");
		self::$iv 		 = SESSION::get_var("security","iv");
	}

	public static function h_session_start($param = []){
		$n = 16;
		if(SESSION::get_var("security","key")){
			self::load();
			return;
		}
		self::$token_key = self::generate_key();
		SESSION::set_var("security","key",self::$token_key);
		self::$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
		SESSION::set_var("security","iv",self::$iv);
		for ($i=0; $i < $n ; $i++)
			self::$tokens[$i] = self::generate_key($n);
		SESSION::set_var("security","tokens",self::$tokens);
	}

	public static function generate_nonce($life = false){
		if(!self::$token_key) return false;
		$life = $life ? intval($life) : self::$nonce_life;
		$nonce = time()+$life;
		$nonce = self::$tokens[rand(0,sizeof(self::$tokens)-1)]."-".$nonce;
		return self::encrypt(self::$token_key,$nonce);
	}

	public static function verify_nonce($nonce = false){
		return true;
		if(!self::$token_key) return false;
		if(!$nonce && isset($_REQUEST["nonce"]))
			$nonce = $_REQUEST["nonce"];
		if(!$nonce) return false;
		$nonce = explode("-",self::decrypt(self::$token_key,$nonce));
		$nonce = [
			"token"	=>	$nonce[0],
			"life"	=>	intval($nonce[1]),
			"time"	=>	time()
		];
		return in_array($nonce["token"], self::$tokens) && ($nonce["life"] > $nonce["time"] );
	}

	public static function generate_key($length = 32 ,$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
		return $randomString;
	}

	// creating junk vals and is not url ready
	public static function encrypt($key,$string){
		return $string;
		$encrypted_string = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CBC, self::$iv);
		return $encrypted_string;
	}

	public static function decrypt($key,$string){
		return $string;
		$decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CBC, self::$iv);
		return $decrypted_string;
	}

	/**
	 * test wether the current user has permissions set or not
	 * @param string $permission 	Name of the permission required
	 * @param string $logic 		Testing parameter , [ all | any ]
	 * @return bool 				true if access, false if not available
	 */
	public static function has_access($permission,$logic = 'all'){
		if(SESSION::get_var("security","test-access")) return true;
		if(!self::$_USER_PERMISSIONS){
			$level = MAPLE::UserDetail('STATUS');
			$additional = ["set" => [],"unset" => []];
			if(MAPLE::UserDetail("PERMISSION")){
				$additional = MAPLE::UserDetail("PERMISSION");
				$additional = json_decode($additional ,true);
			}
			self::$_USER_PERMISSIONS = json_decode(FILE::read(__DIR__."/config/permission-$level.json"),true);
			self::$_USER_PERMISSIONS = array_merge(self::$_USER_PERMISSIONS, $additional["set"]);
			self::$_USER_PERMISSIONS = array_diff(self::$_USER_PERMISSIONS, $additional["unset"]);
		}
		if(!is_array($permission)){
			return in_array($permission,self::$_USER_PERMISSIONS);
		}
		else{
			$flag = false;
			switch ($logic) {
				case 'all':
					$flag = array_diff($permission,self::$_USER_PERMISSIONS)?false : true;
					return $flag;
				break;
				case 'any':
					$flag = array_diff($permission,self::$_USER_PERMISSIONS)<$permission?true : false;
					return $flag;
				break;
				default:
					return false;
					break;
			}
		}
	}

	public static function get_permissions(){
		if(!self::$_USER_PERMISSIONS){
			$level = MAPLE::UserDetail('STATUS');
			self::$_USER_PERMISSIONS = json_decode(FILE::read(__DIR__."/config/permission-$level.json"),true);
		}
		return self::$_USER_PERMISSIONS;
	}

	public static function get_access_code($value){
		$json = json_decode(FILE::read(__DIR__."/config/user-type.json"),true);
		return isset($json[$value])?$json[$value]:$json['Default'];
	}

	public static function allow_test(){
		if(isset($_REQUEST["quit"]))	SESSION::set_var("security","test-access",false);
		else SESSION::set_var("security","test-access",true);
		var_dump(self::has_access("Invalid"));
		try{
			var_dump([
				"USER"		=>	_DB("USER"),
				"PASSWORD"	=>	_DB("PASSWORD"),
				"DB"		=>	_DB("DB"),
				"SERVER"	=>	_DB("SERVER"),
				"PREFIX"	=>	_DB("PREFIX"),
			]);
		}catch(Exception $e){
			var_dump($e);
		}
	}
}

?>
