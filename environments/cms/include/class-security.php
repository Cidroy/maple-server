<?php
namespace maple\cms;

/**
 * Security handler
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class SECURITY {
	/**
	 * Default permission to be granted to client
	 * @var array
	 */
	const default_user_permission = ["grant" => [],"deny" => []];

	/**
	 * Primary user groups
	 * @var array
	 */
	const primary_user_groups = [
		"default"		=>	0,
		"subscriber"	=>	500,
		"editor"		=>	1000,
		"author"		=>	2000,
		"administrator"	=>	3000,
	];

	/**
	 * Nonce Request Name
	 * @var string
	 */
	const nonce_request_name = "nonce";

	/**
	 * permissions folder
	 * @var file-path
	 */
	const _permission_location = \ROOT.\CONFIG."/permissions";
	/**
	 * Default Nonce Life
	 * @var integer
	 */
	const _nonce_life = 24*60*60;
	/**
	 * token key
	 * @var string key
	 */
	private static $token_key = false;
	/**
	 * Tokens
	 * @var array
	 */
	private static $tokens 	  = [];
	/**
	 * encryption iv
	 * @var object
	 */
	private static $iv = false;
	/**
	 * initialisation status
	 * @var boolean
	 */
	private static $_initialized = false;
	/**
	 * User permissions
	 * @var array
	 */
	private static $_USER_PERMISSIONS = false;

	/**
	 * Buffer for user group definitions
	 * @var array
	 */
	private static $_user_group = [];

	/**
	 * Buffer for user group aliases based on namespace
	 * namespace : { alias-name : access-code }
	 * @var array
	 */
	private static $_user_group_alias = [];

	/**
	 * load from session
	 * @uses SESSION::get
	 */
	private static function load_session(){
		self::$token_key = SESSION::get("maple/security","key");
		self::$tokens 	 = SESSION::get("maple/security","tokens");
		self::$iv 		 = SESSION::get("maple/security","iv");

	}

	/**
	 * Initialize
	 * @uses SESSION::active
	 * @throws \RuntimeException if session not active
	 */
	public static function initialize(){
		if(!SESSION::active()) throw new \RuntimeException("'session' not started", 1);

		if(!file_exists(self::_permission_location."/user-type.json")){
			if(!file_exists(self::_permission_location)) mkdir(self::_permission_location,0777,true);
			foreach (self::primary_user_groups as $name => $code) file_put_contents(self::_permission_location."/{$code}.json",json_encode([]));
			file_put_contents(self::_permission_location."/user-type.json",json_encode(self::primary_user_groups));
		}
		if(!file_exists(self::_permission_location."/user-alias.json")) file_put_contents(self::_permission_location."/user-alias.json",json_encode([]));
		self::$_user_group = json_decode(file_get_contents(self::_permission_location."/user-type.json"),true);
		self::$_user_group_alias = json_decode(file_get_contents(self::_permission_location."/user-alias.json"),true);
		asort(self::$_user_group);
		self::$_initialized = true;

		$n = 16;
		if(SESSION::get("maple/security","key")){
			self::load_session();
			return;
		}
		self::$token_key = self::generate_key();
		SESSION::set("maple/security","key",self::$token_key);
		if(function_exists("mcrypt_create_iv"))
			self::$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
		else self::$iv = self::generate_key();
		SESSION::set("maple/security","iv",self::$iv);
		for ($i=0; $i < $n ; $i++)
			self::$tokens[$i] = self::generate_key($n);
		SESSION::set("maple/security","tokens",self::$tokens);


	}

	/**
	 * Return SECURITY initialisation status
	 * @return boolean status
	 */
	public static function initialized(){ return self::$_initialized; }

	/**
	 * Generate time bound Nonce
	 * @throws \InvalidArgumentException if $life is not of type 'integer'
	 * @param  integer $life nonce life span
	 * @return string        nonce
	 */
	public static function generate_nonce($life = false){
		if(!self::$token_key) return false;
		$life = $life ? intval($life) : self::_nonce_life;
		$nonce = time()+$life;
		$nonce = self::$tokens[rand(0,sizeof(self::$tokens)-1)]."-".$nonce;
		return self::encrypt(self::$token_key,$nonce);
	}

	/**
	 * Check if Nonce Requested
	 * @return boolean status
	 */
	public static function is_nonce(){ return isset($_REQUEST[self::nonce_request_name]); }

	/**
	 * Verify Time bound nonce
	 * @throws \InvalidArgumentException if $nonce is not of type 'string'
	 * @param  string $nonce nonce
	 * if false autodetects nonce from request
	 * @return boolean         status
	 */
	public static function verify_nonce($nonce = false){
		if(!self::$token_key) return false;
		if(!$nonce && isset($_REQUEST[self::nonce_request_name]))
			$nonce = $_REQUEST[self::nonce_request_name];
		if(!$nonce) return false;
		if(!is_string($nonce)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		$nonce = explode("-",self::decrypt(self::$token_key,$nonce));
		$nonce = [
			"token"	=>	$nonce[0],
			"life"	=>	intval($nonce[1]),
			"time"	=>	time()
		];
		return in_array($nonce["token"], self::$tokens) && ($nonce["life"] > $nonce["time"] );
	}

	/**
	 * Generate n lenght encryption key
	 * @param  integer $length     key length
	 * @param  string  $characters optional. Characters to use
	 * @return boolean              key
	 */
	public static function generate_key($length = 32 ,$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
		return $randomString;
	}

	/**
	 * encrypt string with key
	 * @throws \InvalidArgumentException if $key or $string is not of type 'string'
	 * @param  string $key    key
	 * @param  string $string string
	 * @return string         encrypted string
	 */
	public static function encrypt($key,$string){
		if(!is_string($key)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($string)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 2);
		// $encrypted_string = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CBC, self::$iv);
		return base64_encode($string);
	}

	/**
	 * decrypt string with key
	 * @throws \InvalidArgumentException if $key or $string is not of type 'string'
	 * @param  string $key    key
	 * @param  string $string string
	 * @return string         decrypted string
	 */
	public static function decrypt($key,$string){
		if(!is_string($key)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($string)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 2);
		return base64_decode($string);
		// $decrypted_string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CBC, self::$iv);
		return $decrypted_string;
	}

	/**
	 * test wether the current user has permissions set or not
	 * @throws \InvalidArgumentException if $permission is not of type 'array'
	 * @throws \DomainException if invalid input for $logic
	 * @param string $namespace 	Permission Namespace
	 * @param string $permission 	Name of the permission required
	 * @param string $logic 		Testing parameter
	 * valid inputs
	 * - all
	 * - *
	 * - any
	 * @return bool 				true if access, false if not available
	 */
	public static function permission($namespace,$permissions = [],$logic = 'all'){
		if(!self::$_USER_PERMISSIONS) self::get_permissions();
		if($namespace && !is_string($namespace)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		if(!is_string($permissions) && !is_array($permissions)) throw new \InvalidArgumentException("Argument #2 should be of type 'string' or 'array'", 2);

		$flag = false;
		if(is_array($permissions)) switch ($logic) {
			case '*':
			case 'any':
				foreach ($permissions as $namespace => $permission)
					if(isset(self::$_USER_PERMISSIONS[$namespace]) && in_array($permission,self::$_USER_PERMISSIONS[$namespace])) return true;
				return false;
			break;
			case 'all':
				foreach ($permissions as $namespace => $permission)
					if(!isset(self::$_USER_PERMISSIONS[$namespace]) || !in_array($permission,self::$_USER_PERMISSIONS[$namespace])) return false;
				return true;
			break;
			default: throw new \DomainException("Invalid value for Argument #3", 1); break;
		}
		else if(is_string($permissions)){
			if(isset(self::$_USER_PERMISSIONS[$namespace]) && in_array($permissions,self::$_USER_PERMISSIONS[$namespace])) return true;
		}
		return false;
	}

	/**
	 * Get permissions list for user
	 * @uses USER::access_level
	 * @uses USER::permissions
	 * @param boolean $reinit re-populate the permissions
	 * @return array permission list
	 */
	public static function get_permissions($reinit = false){
		if(!self::$_USER_PERMISSIONS || $reinit){
			$level = USER::access_level();
			if($level===false) $level = self::get_user_group_code("default");
			$additional = self::default_user_permission;
			if(USER::permissions()){
				$additional = USER::permissions();
			}
			self::$_USER_PERMISSIONS = json_decode(file_get_contents(self::_permission_location."/{$level}.json"),true);
			foreach ($additional["grant"] as $namespace => $permissions) {
				if(isset(self::$_USER_PERMISSIONS[$namespace])) self::$_USER_PERMISSIONS[$namespace] = [];
				self::$_USER_PERMISSIONS[$namespace] = array_merge(self::$_USER_PERMISSIONS[$namespace], $additional["grant"][$namespace]);
			}
			foreach ($additional["deny"] as $namespace => $permissions) {
				if(isset(self::$_USER_PERMISSIONS[$namespace])) self::$_USER_PERMISSIONS[$namespace] = [];
				self::$_USER_PERMISSIONS[$namespace] = array_diff(self::$_USER_PERMISSIONS[$namespace], $additional["deny"][$namespace]);
			}
		}
		return self::$_USER_PERMISSIONS;
	}

	/**
	 * Get user group code based on name
	 * does not check for aliases until a valid namespace is provided
	 * @throws \InvalidArgumentException if $name is not of type 'string'
	 * @param  string $name access name
	 * @param  boolean $namespace check for aliases in namespace
	 * @return integer        access code
	 */
	public static function get_user_group_code($name,$namespace = false){
		$name = strtolower($name);
		if(!is_string($name)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		return isset(self::$_user_group[$name])?
					self::$_user_group[$name]:
					($namespace && isset(self::$_user_group_alias[$namespace]) && isset(self::$_user_group_alias[$namespace][$name]) ?
						self::$_user_group_alias[$namespace][$name]:
						false
					);
	}

	/**
	 * Proxy function to validate if a usergroup exists
	 * if a $namespace is not provided then it is checked in primary user group
	 *
	 * @param string $name group name
	 * @param string $namespace user group namespace
	 * @return boolean status
	 */
	public static function user_group_exists($name,$namespace = false){ return self::get_user_group_code($name,$namespace)!==false; }

	/**
	 * get user group name based on code
	 * if namespace is provided then the check is based on namespace
	 * @throws \InvalidArgumentException if $code is not of type 'integer'
	 * @param  integer $code group code
	 * @param string $namespace plugin namespace
	 * @return string       group name
	 */
	public static function get_user_group_name($code, $namespace = false){
		if (!is_int($code)) throw new \InvalidArgumentException("Argument #1 should be of type 'integer'", 1);
		if ($namespace && !is_string($namespace)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 2);
		if (!$namespace) return array_search($code, self::$_user_group);
		else return isset(self::$_user_group_alias[$namespace]) ? array_search($code, self::$_user_group_alias[$namespace]) : false;
	}

	/**
	 * Add new user group alias.
	 * $original must not be an alias user group
	 * @permission maple/security:user-group-alias|add
	 * @filter user-group-alias|added
	 * @maintainance 'maple/security user-group-alias|add
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if does not have permission maple/security:user-group-alias|add
	 * @throws \InvalidArgumentException if $namespace,$alias,$original is not of type 'string'
	 * @throws \DomainException if $alias is already a user group alias in $namespace
	 * @throws \DomainException if $original is not a user group
	 * @param string $namespace alias namespace
	 * @param string $alias alias name
	 * @param string $original original user group
	 * @return integer access code
	 */
	public static function user_group_alias_add($namespace,$alias,$original){
		if (!self::permission("maple/security", "user-group-alias|add")) throw new \maple\cms\exceptions\InsufficientPermissionException("insufficient permission", 1);
		if (!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 2);
		if (!is_string($alias)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 3);
		if (!is_string($original)) throw new \InvalidArgumentException("Argument #3 should be of type 'string'", 4);
		if (self::get_user_group_code($alias,$namespace)!==false) throw new \DomainException("Alias already exists", 5);
		$code = self::get_user_group_code($original);
		if ($code===false) throw new \DomainException("invalid user group", 6);

		self::$_user_group_alias[$namespace][$alias] = $code;
		self::_user_group_alias_changes_save("maple/security user-group-alias|add");
		MAPLE::do_filters("user-group-alias|added", $filter = ["added" => $code]);
		return $code;
	}

	/**
	 * rename user group alias.
	 * @permission maple/security:user-group-alias|edit
	 * @filter user-group-alias|renamed
	 * @maintainance 'maple/security user-group-alias|renamed
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if does not have permission maple/security:user-group-alias|edit
	 * @throws \InvalidArgumentException if $namespace,$alias,$new is not of type 'string'
	 * @throws \DomainException if $new is already a user group alias in $namespace
	 * @throws \DomainException if $alias is not already a user group alias in $namespace
	 * @param string $namespace alias namespace
	 * @param string $alias alias name
	 * @param string $new new user group
	 * @return boolean status
	 */
	public static function user_group_alias_rename($namespace, $alias, $new){
		if (!self::permission("maple/security", "user-group-alias|edit")) throw new \maple\cms\exceptions\InsufficientPermissionException("insufficient permission", 1);
		if (!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 2);
		if (!is_string($alias)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 3);
		if (!is_string($new)) throw new \InvalidArgumentException("Argument #3 should be of type 'string'", 4);
		if (self::get_user_group_code($new, $namespace) !== false) throw new \DomainException("Alias name already exists", 5);
		$code = self::get_user_group_code($alias);
		if ($code === false) throw new \DomainException("Alias does not exists", 6);

		self::$_user_group_alias[$namespace][$new] = $code;
		unset(self::$_user_group_alias[$namespace][$alias]);
		self::_user_group_alias_changes_save("maple/security user-group-alias|renamed");
		MAPLE::do_filters("user-group-alias|renamed", $filter = ["edited" => $code]);
		return true;
	}

	/**
	 * rename user group alias.
	 * @permission maple/security:user-group-alias|delete
	 * @filter user-group-alias|deleted
	 * @maintainance 'maple/security user-group-alias|deleted
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if does not have permission maple/security:user-group-alias|delete
	 * @throws \InvalidArgumentException if $namespace,$alias,$new is not of type 'string'
	 * @throws \DomainException if $new is already a user group alias in $namespace
	 * @throws \DomainException if $alias is not already a user group alias in $namespace
	 * @param string $namespace alias namespace
	 * @param string $alias alias name
	 * @return boolean status
	 */
	public static function user_group_alias_delete($namespace, $alias){
		if (!self::permission("maple/security", "user-group-alias|delete")) throw new \maple\cms\exceptions\InsufficientPermissionException("insufficient permission", 1);
		if (!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 2);
		if (!is_string($alias)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 3);
		$code = self::get_user_group_code($alias);
		if ($code === false) return false;

		unset(self::$_user_group_alias[$namespace][$alias]);
		self::_user_group_alias_changes_save("maple/security user-group-alias|deleted");
		MAPLE::do_filters("user-group-alias|deleted", $filter = ["deleted" => $code]);
		return true;
	}

	/**
	 * Add new user group.
	 * if specified access level already exists then it returns false
	 * @permission maple/security:user-group|add
	 * @filter user-group|added
	 * @maintainance 'maple/security user-group|add'
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if does not have permission maple/security:user-group|add
	 * @throws \DomainException if $name is already a user group
	 * @throws \DomainException if $level is not properly formatted
	 * @throws \InvalidArgumentException if $name is not of type 'string'
	 * @throws \InvalidArgumentException if $level is not of type 'integer' or 'array'
	 * @param string  $name  accesss level name
	 * @param mixed[integer,array] $level access level
	 * if $level is an array then
	 * 		array $level {
	 * 			@type integer 'min' minimum access level
	 * 			@type integer 'max' maximum access level
	 * 		}
	 * 		to remove any side of limit please do not add that limit.
	 * 		NOTE : the minimum difference between min and max should be 2,
	 * 		       else a \DomainException is thrown.
	 * @return integer access code
	 */
	public static function user_group_add($name,$level = 0){
		if(!self::permission("maple/security","user-group|add")) throw new \maple\cms\exceptions\InsufficientPermissionException("insufficient permission", 1);
		if(!is_string($name)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 2);
		if(!is_array($level) && !is_integer($level)) throw new \InvalidArgumentException("Argument #2 should be of type 'integer' or 'array'", 3);
		if(self::get_user_group_code($name)!==false) throw new \DomainException("User Group '{$name}' already exists, try another.", 4);

		$name = strtolower($name);
		$permission = 0;
		if(is_integer($level)){
			if(array_search($level,self::$_user_group)!==false) return false;
			$permission = $level;
		}
		else if(is_array($level)){
			if(!isset($level["min"]) && !isset($level["max"])) throw new \DomainException("Invalid Format for Argument #2", 5);
			if(isset($level["min"]) || isset($level["max"]) && !(isset($level["min"]) && isset($level["max"]))){
				$buffer = self::$_user_group;
				if (isset($level["min"])){
					if( !is_integer($level["min"])) throw new \DomainException("Invalid Format for Argument #2", 6);
					$min = $level["min"];
					if(!self::get_user_group_name($min)){
						$buffer[$name] = $min;
						asort($buffer);
					}
					reset($buffer);
					while(current($buffer)!==$buffer[array_search($min,$buffer)]) next($buffer);
					$buffer = next($buffer);
					$level["max"] = $buffer?$buffer:$min+100;
				}
				else {
					if( !is_integer($level["max"])) throw new \DomainException("Invalid Format for Argument #2", 7);
					$max = $level["max"];
					if(!self::get_user_group_name($max)){
						$buffer[$name] = $max;
						asort($buffer);
					}
					reset($buffer);
					while(current($buffer)!==$buffer[array_search($max,$buffer)]) next($buffer);
					$buffer = prev($buffer);
					$level["min"] = $buffer?$buffer:$max-100;
				}
			}
			if( !is_integer($level["min"]) || !is_integer($level["min"]) || $level["max"]<=$level["min"] || !($level["max"]-$level["min"] > 1 )) throw new \DomainException("Invalid Format for Argument #2", 1);
			$permission = $level["min"];
			$level = intval($level["min"] + ($level["max"]-$level["min"])/2);
		}
		$buffer = self::$_user_group;
		$buffer[$name] = $permission;
		asort($buffer);
		while(current($buffer)!==$buffer[array_search($permission,$buffer)]) next($buffer);
		$permission = prev($buffer);
		$permission = $permission!==false?
			json_decode(file_get_contents(self::_permission_location."/{$permission}.json"),true):
			[]
		;
		self::$_user_group[$name] = $level;
		file_put_contents(self::_permission_location."/{$level}.json",json_encode($permission));
		self::_user_group_changes_save("maple/security user-group|add");
		MAPLE::do_filters("user-group|added",$filter = [ "added"	=>	$name ]);
		return $level;
	}

	/**
	 * Rename a user group
	 * @permission maple/security:user-group|rename
	 * @filter 'user-group|renamed'
	 * @maintainance 'maple/security user-group|rename'
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if does not have permission maple/security:user-group|rename
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if trying to rename primary user group
	 * @throws \InvalidArgumentException if $original or $new is not of type string
	 * @throws \DomainException if $new name already exists
	 * @param  string $original original name
	 * @param  string $new      new name
	 * @return boolean           change status
	 */
	public static function user_group_rename($original,$new){
		if(!self::permission("maple/security","user-group|rename")) throw new \maple\cms\exceptions\InsufficientPermissionException("", 1);
		if(!is_string($original)) throw new \InvalidArgumentException("Argument #1 should be of type 'string' and not empty", 1);
		if(!is_string($new)) throw new \InvalidArgumentException("Argument #2 should be of type 'string' and not empty", 1);
		if(!isset(self::$_user_group[$original])) throw new \DomainException("Invalid User Group '{$original}'", 1);
		if(array_key_exists($original,self::primary_user_groups)) throw new \maple\cms\exceptions\InsufficientPermissionException("Cannot Modify Primary User Group", 1);
		if(isset(self::$_user_group[$new])) return false;
		self::$_user_group[$new] = self::$_user_group[$original];
		unset(self::$_user_group[$original]);
		self::_user_group_changes_save("maple/security user-group|rename");
		MAPLE::do_filters("user-group|renamed",$filter = ["original-name" => $original,"new-name" => $new]);
		return true;
	}

	/**
	 * Delete a User group by code
	 * @permission maple/security:user-group|delete
	 * @filter "user-group|deleted"
	 * @maintainance "maple/security user-group|delete"
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if does not have permission maple/security:user-group|delete
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if user group is a primary user group
	 * @throws \InvalidArgumentException if $code not of type 'integer'
	 * @param  integer $code user group code
	 * @return boolean       status
	 */
	public static function user_group_delete($code){
		if(!self::permission("maple/security","user-group|delete")) throw new \maple\cms\exceptions\InsufficientPermissionException("", 1);
		if(!is_integer($range)) throw new \InvalidArgumentException("Argument #1 should be of type 'integer'", 2);
		if(in_array($code,self::primary_user_groups)) throw new \maple\cms\exceptions\InsufficientPermissionException("Cannot Remove Primary User group", 3);
		$key = self::get_user_group_name($code);
		if(!$key) return false;
		unset(self::$_user_group[$key]);
		unlink(self::_permission_location."/{$code}.json");
		self::_user_group_changes_save("maple/security user-group|delete");
		MAPLE::do_filters("user-group|deleted",$filter = ["deleted"=>$code]);
		return true;
	}

	/**
	 * Return list of user groups
	 * @return array user groups
	 */
	public static function user_groups(){ return array_flip(self::$_user_group); }

	/**
	 * Return list of aliases for the user group
	 * @throws \InvalidArgumentException if $namespace not of type 'string'
	 * @param string $namespace username
	 * @return array
	 */
	public static function user_group_aliases($namespace) {
		if (!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		return isset(self::$_user_group_alias[$namespace])? self::$_user_group_alias[$namespace]: []; 
	}

	/**
	 * Save the User Group Modification made in self::$_user_group
	 * @maintainance $change
	 * @throws \InvalidArgumentException if $change not of type 'string'
	 * @param  string $change task name
	 */
	private static function _user_group_changes_save($change){
		if(!is_string($change) || !$change) throw new \InvalidArgumentException("Argument #1 should be of type 'string' and not empty", 1);
		asort(self::$_user_group);
		\ENVIRONMENT::lock("maple/cms : {$change}");
			file_put_contents(self::_permission_location."/user-type.json",json_encode(self::$_user_group));
		\ENVIRONMENT::unlock();
	}

	/**
	 * Save the User Group Made Modification made in self::$_user_group_alias
	 * @maintainance $change
	 * @throws \InvalidArgumentException if $change not of type 'string'
	 * @param  string $change task name
	 */
	private static function _user_group_alias_changes_save($change){
		if (!is_string($change) || !$change) throw new \InvalidArgumentException("Argument #1 should be of type 'string' and not empty", 1);
		asort(self::$_user_group_alias);
		\ENVIRONMENT::lock("maple/cms : {$change}");
		file_put_contents(self::_permission_location . "/user-alias.json", json_encode(self::$_user_group_alias));
		\ENVIRONMENT::unlock();
	}

	/**
	 * Grant Permission to User Group with code
	 * @filter user-group-permission|modified
	 * @permission maple/security:user-group-permission|grant
	 * @maintainance 'maple/security user-group-permission|modified'
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if does not have permission maple/security:user-group-permission|grant
	 * @throws \InvalidArgumentException if $code is not of type 'integer'
	 * @throws \InvalidArgumentException if $namespace is not of type 'string'
	 * @throws \InvalidArgumentException if $permission is not of type 'string' or 'array'
	 * @throws \DomainException if $code is not of a valid user group
	 * @param  integer $code       User Group Code
	 * @param  string $namespace  permission namespace
	 * @param  mixed[string,array] $permission permission(s)
	 * @return boolean             status
	 */
	public static function grant_permission($code,$namespace,$permission){
		if(!self::permission("maple/security","user-group-permission|grant")) throw new \maple\cms\exceptions\InsufficientPermissionException("insufficient permission", 1);
		if(!is_integer($code)) throw new \InvalidArgumentException("Argument #1 should be of type 'integer'", 2);
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 3);
		if(!is_string($permission)||!is_array($permission)) throw new \InvalidArgumentException("Argument #3 should be of type 'string' or 'array'", 4);
		if(!self::get_user_group_name($code)) throw new \DomainException("Invalid User Group Code", 5);

		$permissions = json_decode(file_get_contents(self::_permission_location."/{$code}.json"),true);
		if(!isset($permissions[$namespace])) $permissions[$namespace] = [];
		if(is_array($permission)) $permissions[$namespace] = array_merge($permissions[$namespace],$permission);
		else if (is_string($permission) && !in_array($permission,$permissions[$namespace]) ) $permissions[$namespace][] = $permission;
		sort($permissions[$namespace]);
		$permissions[$namespace] = array_values($permissions[$namespace]);

		\ENVIRONMENT::lock("maple/cms : maple/security user-group-permission|modified");
			file_put_contents(self::_permission_location."/{$code}.json",json_encode($permissions));
			MAPLE::do_filters("user-group-permission|modified",$filter = [
				"group"	=>	$code,
				"time"	=>	time(),
				"namespace"	=>	$namespace
			]);
		\ENVIRONMENT::unlock();
		return true;
	}

	/**
	 * Grant Permission to User Group with code
	 * @filter user-group-permission|modified
	 * @permission maple/security:user-group-permission|deny
	 * @maintainance 'maple/security user-group-permission|modified'
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if does not have permission maple/security:user-group-permission|deny
	 * @throws \InvalidArgumentException if $code is not of type 'integer'
	 * @throws \InvalidArgumentException if $namespace is not of type 'string'
	 * @throws \InvalidArgumentException if $permission is not of type 'string' or 'array'
	 * @throws \DomainException if $code is not of a valid user group
	 * @param  integer $code       User Group Code
	 * @param  string $namespace  permission namespace
	 * @param  mixed[string,array] $permission permission(s)
	 * @return boolean             status
	 */
	public static function deny_permission($code,$namespace,$permission){
		if(!self::permission("maple/security","user-group-permission|deny")) throw new \maple\cms\exceptions\InsufficientPermissionException("insufficient permission", 1);
		if(!is_integer($code)) throw new \InvalidArgumentException("Argument #1 should be of type 'integer'", 2);
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 3);
		if(!is_string($permission)||!is_array($permission)) throw new \InvalidArgumentException("Argument #3 should be of type 'string' or 'array'", 4);
		if(!self::get_user_group_name($code)) throw new \DomainException("Invalid User Group Code", 5);

		$permissions = json_decode(file_get_contents(self::_permission_location."/{$code}.json"),true);
		if(is_array($permission)) $permissions[$namespace] = array_diff($permissions[$namespace],$permission);
		else if (is_string($permission) && in_array($permission,$permissions[$namespace]) ) unset($permissions[$namespace][array_search($permission,$permissions[$namespace])]);
		sort($permissions[$namespace]);
		$permissions[$namespace] = array_values($permissions[$namespace]);

		\ENVIRONMENT::lock("maple/cms : maple/security user-group-permission|modified");
			file_put_contents(self::_permission_location."/{$code}.json",json_encode($permissions));
			MAPLE::do_filters("user-group-permission|modified",$filter = [
				"group"	=>	$code,
				"time"	=>	time(),
				"namespace"	=>	$namespace
			]);
		\ENVIRONMENT::unlock();
		return true;
	}

	/**
	 * String to Permitted Group Codes,
	 * Get an array of group code by parsing syntax
	 * accepts type 
	 * "*"		=> all,
	 * "group+" => all group above and including,
	 * "group-" => all group below and excluding,
	 * "group1-group2" => all groups in range,
	 * "group1,group2,group3" => specifically these,
	 * "~groupX"=> exclude group, can be used in combination. eg: "group(+|-),~groupX",
	 * "namespace:group" => to use alias use this format
	 * @throws \InvalidArgumentException if $str not of type string
	 * @throws \DomainException if $str is not in a valid Format
	 * @param  string $str permission syntax
	 * @return array      codes
	 */
	public static function str_to_permitted_groupcodes($str = ""){
		if(!is_string($str)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		$_group = [
			"allow"	=>	[],
			"deny"	=>	[],
		];
		$__parse = explode(",",$str);
		foreach ($__parse as $access ) {
			$mode = "allow";
			if(preg_match("/^~/",$access)){
				$mode = "deny";
				$access = trim($access,"~");
			}
			else $mode = "allow";
			# match "*"
			if($access=="*") $_group[$mode] = array_merge(array_values(self::$_user_group),$_group[$mode]);
			# match "a_z+"
			else if(preg_match("/^[a-zA-Z0-9_\/:]+\+$/",$access)){
				$access = explode(":",$access);
				$namespace = isset($access[1])?$access[0]:null;
				$access = rtrim(isset($access[1]) ? $access[1] : $access[0],"+");
				$min_code = self::get_user_group_code($access,$namespace);
				if($min_code===false) continue;
				foreach (self::$_user_group as $name => $code) if($code >= $min_code) $_group[$mode][] = $code;
			}
			# match "a_z-"
			else if(preg_match("/^[a-zA-Z0-9_\/:]+\-$/",$access)){
				$access = explode(":", $access);
				$namespace = isset($access[1]) ? $access[0] : null;
				$access = rtrim(isset($access[1]) ? $access[1] : $access[0], "-");
				$min_code = self::get_user_group_code($access, $namespace);
				if($min_code===false) continue;
				foreach (self::$_user_group as $name => $code) if($code <= $min_code) $_group[$mode][] = $code;
			}
			# match "a-b"
			else if(preg_match("/^[a-zA-Z0-9_\/:]+\-[a-zA-Z0-9_\/:]+$/",$access)){
				$access = explode("-",$access);
				$access[0] = explode(":",$access[0]);
				$access[1] = explode(":",$access[1]);
				$groups = [[],[]];
				$groups[0]["namespace"] = isset($access[0][1]) ? $access[0][0] : null;
				$groups[0]["access"]	= isset($access[0][1]) ? $access[0][1] : $access[0][0];
				$groups[1]["namespace"] = isset($access[1][1]) ? $access[1][0] : null;
				$groups[1]["access"]	= isset($access[1][1]) ? $access[1][1] : $access[1][0];
				$min_code = self::get_user_group_code($groups[0]["access"],$groups[0]["namespace"]);
				$max_code = self::get_user_group_code($groups[1]["access"],$groups[1]["namespace"]);
				foreach (self::$_user_group as $name => $code) if($min_code <= $code && $code <= $max_code) $_group[$mode][] = $code;
			}
			# match "a"
			else{
				$access = explode(":", $access);
				$namespace = isset($access[1]) ? $access[0] : null;
				$access = isset($access[1]) ? $access[1] : $access[0];
				if(($code = self::get_user_group_code($access,$namespace))!==false) $_group[$mode][] = $code;
			}
		}
		$allowed = array_diff(array_unique($_group["allow"]),array_unique($_group["deny"]));
		sort($allowed);
		return $allowed;
	}

	/**
	 * Install Permissions from File
	 * @param  string $namespace app namespace
	 * @param  string $folder    app folder path
	 */
	public static function install_permission($namespace,$folder){
		if(!file_exists("{$folder}/permissions.json")) return;
		$user_codes=json_decode(file_get_contents(self::_permission_location."/user-type.json"),true);
		$user_codes = array_flip($user_codes);
		foreach ($user_codes as $key => $value) $user_codes[$key] = json_decode(file_get_contents(self::_permission_location."/{$key}.json"),true);
		$plugin_permissions = json_decode(file_get_contents("{$folder}/permissions.json"),true);
		foreach ($plugin_permissions as $permission) {
			$permission["namespace"] = isset($permission["namespace"])?$permission["namespace"]:$namespace;
			$permission["access"] = self::str_to_permitted_groupcodes($permission["access"]);
			foreach ($permission["access"] as $group) {
				if(!isset($user_codes[$group][$permission["namespace"]])) $user_codes[$group][$permission["namespace"]] = [];
				if(!in_array($permission["name"],$user_codes[$group][$permission["namespace"]])) $user_codes[$group][$permission["namespace"]][] = $permission["name"];
			}
		}
		foreach ($user_codes as $key => $value) file_put_contents(self::_permission_location."/{$key}.json",json_encode($value));
	}

	/**
	 * Uninstall permissions
	 * @param  string $namespace app namespace
	 * @param  string $folder  app path
	 */
	public static function uninstall_permission($namespace,$folder){
		if(!file_exists("{$folder}/permissions.json")) return;
		$user_codes=json_decode(file_get_contents(self::_permission_location."/user-type.json"),true);
		$user_codes = array_flip($user_codes);
		foreach ($user_codes as $key => $value) $user_codes[$key] = json_decode(file_get_contents(self::_permission_location."/{$key}.json"),true);
		$plugin_permissions = json_decode(file_get_contents("{$folder}/permissions.json"),true);
		if(!$plugin_permissions) return;
		foreach ($plugin_permissions as $permission) {
			$permission["namespace"] = isset($permission["namespace"])?$permission["namespace"]:$namespace;
			foreach ($user_codes as $key => $value) {
				unset($user_codes[$key][$permission["namespace"]]);
			}
		}
		foreach ($user_codes as $key => $value) file_put_contents(self::_permission_location."/{$key}.json",json_encode($value));
	}
}

?>
