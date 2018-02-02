<?php 
namespace maple\cms\login;

use maple\cms\URL;
use maple\cms\DB;
use maple\cms\SECURITY;
use Stash\Exception\InvalidArgumentException;


/**
 * This is a proxy class for user related tasks
 * @since 1.0.0
 * @package Maple CMS Login
 * @author Rubixcode
 */
class USER{

	/**
	 * check if user exists
	 * @throws \DomainException if $param is not valid
	 * @param string $param search param
	 * @param mixed search value
	 * @return boolean status
	 * @author Rubixcode 
	 */
	public static function exists($param,$value){
		if(!is_string($param)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!in_array($param,["username","email","name","id"])) throw new \DomainException("invalid exist parameter", 1);		
		return DB::_()->has("users",[ "$param" => $value ]);
	}

	/**
	 * add new user
	 * @throws \InvalidArgumentException if name,username or email is not given
	 * @throws \DomainException if access code is invalid
	 * @throws \Exception if username or email is already registered
	 * @param array $user user details
	 * @return array details
	 * @author Rubixcode 
	 */
	public static function add($user){
		if(!$user["name"]) throw new \InvalidArgumentException("name is required", 1);
		if(!$user["username"]) throw new \InvalidArgumentException("username is required", 2);
		if(!$user["email"]) throw new \InvalidArgumentException("email is required", 3);
		if(!array_key_exists("access",$user)) 
			$user["access"] = SECURITY::get_user_group_code("default");
		else 
			$user["access"] = intval($user["access"]);
		if (\maple\cms\SECURITY::get_user_group_name($user["access"])===false) throw new \DomainException("invalid access for user", 4);

		if(self::exists("username",$user["username"])) throw new \Exception("username already exists", 5);
		if(self::exists("name",$user["name"])) throw new \Exception("name already exists", 6);

		$param = [];
		if($user["name"]) $param["name"] = $user["name"];
		if($user["username"]) $param["username"] = $user["username"];
		if($user["email"]) $param["email"] = $user["email"];
		if($user["password"]) $param["password"] = md5($user["password"]);
		
		$param["access"] = $user["access"];
		$param["author"] = \maple\cms\USER::id();
		$param["#registered"] = "NOW()";
		$param["permissions"] = json_encode(SECURITY::default_user_permission);

		DB::_()->insert("users",$param);
		return [
			"id" => DB::_()->id(),
			"name"=> $param["name"],
			"username"=> $param["username"],
			"email"=> $param["email"],
			"access"=> SECURITY::get_user_group_name($param["access"]),
			"access-code"=> $param["access"],
		];
	}

	/**
	 * Add User
	 * @api
	 * @api-handler "maple/login:user|add",
	 * @response success array {
	 * 		string	"type": "success",
	 * 		int		"type": "success",
	 * 		array	"user": {
	 * 			string "id" : id,
	 * 			string "name" : name,
	 * 			string "email" : email,
	 * 			string "username" : username,
	 * 			string "access" : access,
	 * 			string "access-code" : access-code,
	 * 		}
	 * }
	 * @response error array {
	 * 		string "type" : "error",
	 * 		string "message" : message
	 * 		string "code" : code
	 * }
	 * @return array response
	 * @author Rubixcode
	 */
	public static function a_add(){
		try{
			$user = self::add($_REQUEST);
			return [
				"type" => "success",
				"id"   => $user["id"],
				"user" => $user,
			];
		}catch(\Exception $e){
			return [
				"type" => "error",
				"message" => $e->getMessage(),
				"code"	=>	$e->getCode(),
				"error"	=> 	\DEBUG?$e->getTrace():false,
			];
		}
	}

	/**
	 * get user groups
	 * @api
	 * @api-handler "maple/login:user-group|list"
	 * @response success array usergroups
	 * @return array response
	 * @author Rubixcode
	 */
	public static function a_usergroup_list(){
		return SECURITY::user_groups();
	}

	/**
	 * get user groups
	 * @api
	 * @api-handler "maple/login:user-group|list"
	 * @response success array {
	 * 		string "type" : "success"
	 * 		array  "users": {
	 * 			array id : {
	 * 				string "id" : id,
	 * 				string "name" : name,
	 * 				string "username" : username,
	 * 				string "email" : email,
	 * 				string "access" : access,
	 * 				string "registered" : registered,
	 * 				array  "author" : {
	 * 					string "a_id" : author.id,
	 * 					string "a_username" : author.username
	 * 				}
	 * 			}
	 * 		}
	 * }
	 * @return array response
	 * @author Rubixcode
	 */
	public static function a_user_all(){
		$where = [];
		if($_REQUEST["limit"]) $where["LIMIT"] = [$_REQUEST["offset"]? intval($_REQUEST["offset"]):0, intval($_REQUEST["limit"])];
		$result = DB::_()->select("users", 
			[
				"[>]users(author)" => [ "author"=>"id" ]
			],
			[
				"users.id", "users.name", "users.username", "users.email", "users.access","users.registered",
				"author" => [
					"author.id(a_id)", "author.username(a_username)"
				]
			],
			$where
		);
		$users = [];
		foreach ($result as $row) {
			$users[$row["id"]] = $row;
		}
		return [
			"type" => "success",
			"users"	=> $users,
			"groups"=> SECURITY::user_groups()
		];
	}

	// TODO : Add following capabilities
	// a_user_view
	// a_user_edit
	// a_user_delete
}

?>