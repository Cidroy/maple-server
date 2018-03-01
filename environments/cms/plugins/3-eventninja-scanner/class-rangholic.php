<?php
namespace eventninjaz;

use \maple\cms\DB;
use maple\cms\FILE;

class SCAN_TYPE{
	const verified = "verified";
	const denied = "denied";
}


/**
 * Scan Handler
 * @since 1.0.0
 * @package Eventninjaz Rangholic scanner
 * @author Rubixcode
 */
class RANGHOLIC{

	/**
	 * Config
	 * @var array
	 */
	private static $_config = [];

	/**
	 * Plugin database object
	 *
	 * @var DB::object
	 */
	private static $plugin_db = null;

	/**
	 * Verify that is user is registered
	 * @api
	 * @api-handler "eventninjaz/rangholic:verify"
	 * @request array {
	 * 		string "ticket" : ticked number,
	 * 		string "scanner": scanner id,
	 * }
	 * @respnse success array {
	 * 		string "type" : "success",
	 * 		array  "user" : {
	 * 			string "name" : user name,
	 * 			string "email" : user email,
	 * 		},
	 * 		string "ticket" : ticket id,
	 * 		string "scan-type" : verified|denied
	 * 		string "message" : message,
	 * 		array "verifier" : {
	 * 			time "now" : now,
	 * 			time "time": verification time,
	 * 			string "name": verifier name
	 * 		}
	 * }
	 * @response error array {
	 * 		string "type" : "error",
	 * 		string "message" : message,
	 * 		string "code"	: code,
	 * 		string "error"	: stack trace,
	 * } 
	 * @return array response
	 */
	public static function a_verify(){
		try{
			self::initialize();
			if(!\maple\cms\URL::has_request(["ticket","scanner"])) throw new \Exception("insufficient arguments", 1);
			$_REQUEST["scanner"] = intval($_REQUEST["scanner"]);
			if(!DB::_()->has("evz_rh_user",["id"=>$_REQUEST["scanner"]])) throw new \Exception("invalid scanner", 2);
			$_REQUEST["ticket"] = "#".$_REQUEST["ticket"];
			if(!self::$plugin_db->has("posts",[
				"post_type"	=>	self::$_config["plugin"],
				"post_title"=>	$_REQUEST["ticket"]
			])) 
				throw new \Exception("INVALID TICKET", 3);
			
			$message = "Verified";
			$scan_type = SCAN_TYPE::verified;
			$ticket = self::$plugin_db->get("posts",[
				"[>]users"	=>	[ "post_author" => "ID" ]
			],[
				"posts.post_title(ticket)", "posts.post_date_gmt(issue_time)",
				"user"	=>	["users.user_nicename(name)", "users.user_email(email)", ]
			],[
				"posts.post_type"		=> self::$_config["plugin"],
				"posts.post_title"		=> $_REQUEST["ticket"]				
			]);
			$verifier = [];
			if(DB::_()->has("evz_rh_scanned",[ "post_id"	=>	$_REQUEST["ticket"]])){
				$message = "Ticket already used!";
				$scan_type = SCAN_TYPE::denied;
			} else {
				$message = "Ticket Scanned Successfully";
				$scan_type = SCAN_TYPE::verified;
				DB::_()->insert("evz_rh_scanned",[
					"#time"		=>	"NOW()",
					"post_id"	=>	$_REQUEST["ticket"],
					"by"		=>	$_REQUEST["scanner"],
					"client" 	=> $ticket["user"]["name"],
					"client_mail"=> $ticket["user"]["email"],
				]);
			}
			$scan = DB::_()->get("evz_rh_scanned", "*", ["post_id" => $_REQUEST["ticket"]]);
			$verifier = DB::_()->get("evz_rh_user",[ "name" ],[ "id" => $scan["by"] ]);

			return [
				"type"	=>	"success",
				"user"	=>	[
					"name"	=>	$ticket["user"]["name"],
					"email"	=>	$ticket["user"]["email"],
				],
				"ticket"=>	trim($_REQUEST["ticket"],"#"),
				"scan-type" => $scan_type,
				"verifier"	=> [
					"now"	=>	\maple\cms\TIME::now('GMT'),
					"time"	=>	\maple\cms\TIME::parse($ticket["issue_time"],'GMT'),
					"name"	=>	$verifier["name"],
				]
			];
		}catch(\Exception $e){
			return [
				"type" => "error",
				"message" => $e->getMessage(),
				"code" => $e->getCode(),
				"error" => \DEBUG ? $e->getTrace() : null
			];
		}
	}
	/**
	 * login the scanner
	 * @api
	 * @api-handler "eventninjaz/rangholic:login"
	 * @request {
	 * 		string "email" : email,
	 * }
	 * @response success array {
	 * 		string "type" : "success",
	 * 		string "email": email,
	 * 		string "name": name,
	 * 		string "id": id,
	 * 		array  "scanned" : [
	 * 			{
	 * 				string "ticket" : ticket id,
	 * 				string "client" : client name
	 * 			} ...
	 * 		]
	 * }
	 * @response error array {
	 * 		string "type" : "error",
	 * 		string "message" : message,
	 * 		string "code"	: code,
	 * 		string "error"	: stack trace,
	 * } 
	 * @return array response
	 */
	public static function a_login(){
		try{
			if(!\maple\cms\URL::has_request("email")) throw new \Exception("insufficient argument", 1);
			$email = $_REQUEST["email"];
			if(!DB::_()->has("evz_rh_user",[ "email" => $email ])) throw new \Exception("INVALID CREDENTIALS", 2);
			$scanner = DB::_()->get("evz_rh_user","*",["email" => $email]);
			$scans = DB::_()->select("evz_rh_scanned",[ "post_id(ticket)","client(client)" ],[ "by" => $scanner["id"] ]);
			return [
				"type"	=>	"success",
				"name"	=>	$scanner["name"],
				"email"	=>	$scanner["email"],
				"id"	=>	$scanner["id"],
				"scans"	=>	$scans,
			];
		}catch (\Exception $e) {
			return [
				"type" => "error",
				"message" => $e->getMessage(),
				"code" => $e->getCode(),
				"error" => \DEBUG ? $e->getTrace() : null
			];
		}
	}

	/**
	 * register the user
	 * @api
	 * @api-handler "eventninjaz/rangholic:register"
	 * @response success array {
	 * 		string "type" : "success",
	 * 		string "email": email,
	 * 		string "id": id,
	 * }
	 * @response error array {
	 * 		string "type" : "error",
	 * 		string "message" : message,
	 * 		string "code"	: code,
	 * 		string "error"	: stack trace,
	 * } 
	 * @return array response
	 */
	public static function a_register(){
		try{
			if (!\maple\cms\URL::has_request(["email", "name"])) throw new \Exception("insufficient parameters", 1);
			$name = $_REQUEST["name"];
			$email = $_REQUEST["email"];
			if(DB::_()->has("evz_rh_user",[ "email"	=> $email ])) throw new \Exception("email already registered", 2);
			DB::_()->insert("evz_rh_user",[
				"name"	=>	$name,
				"email"	=>	$email,
				"#created" => "NOW()",
			]);
			if (DB::_()->error() !== ["00000", null, null]) throw new \RuntimeException("something went wrong", 3);
			$id = DB::_()->id();
			return [
				"type"	=>	"success",
				"email"	=>	$email,
				"id"	=>	$id,
			];
		}catch(\Exception $e){
			return [
				"type" => "error",
				"message" => $e->getMessage(),
				"code" => $e->getCode(),
				"error" => \DEBUG ? $e->getTrace() : null
			];
		}
	}

	/**
	 * initialize critical components
	 * @throws \RuntimeException if config doesnt exists
	 * @throws \RuntimeException if posts table is not available
	 */
	public static function initialize(){
		if(!file_exists(__DIR__."/config.json")) throw new \RuntimeException("config file does not exist", 1);
		self::$_config = FILE::read(__DIR__."/config.json",true)["table"];
		self::$plugin_db = DB::modified(["prefix" => self::$_config["prefix"]]);
		$schema = new \maple\cms\database\Schema(self::$plugin_db);
		if(!$schema->table_exists("posts")) throw new \RuntimeException("system requires eventninja ticket plugin on the same database", 2);
	}
}

?>