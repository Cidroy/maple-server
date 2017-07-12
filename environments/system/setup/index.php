<?php
namespace maple\environments;
require_once "ini.php";
if(isset($_REQUEST["environment-ajax-action"])){
	session_start();
	if(BOOT::loggedin()){
		switch ($_REQUEST["environment-ajax-action"]) {
			case 'activate':
				try {
					if(!isset($_REQUEST["environment"]))
						throw new \InvalidArgumentException("environment is missing", 1);
					// TODO : !important! a lot of stuff
					$res = \ENVIRONMENT::activate([
						"environment"	=>	$_REQUEST["environment"],
						"method"		=>	@$_REQUEST["method"],
						"set"			=>	@$_REQUEST["set"],
						"reference"		=>	@$_REQUEST["reference"],
					]);
					if($res["type"] == "success"){
						header("Location: ".\ENVIRONMENT::url()->root(false).$res["environment"]["post-install"].(substr($res["environment"]["post-install"],-1)=="/"?"":"/"));
					} else header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel."?".http_build_query([
						"page"	=>	"install",
						"message"=>	$res["message"],
					]));
				} catch (\Exception $e) {
					header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel."?".http_build_query([
						"page"	=>	"install",
						"message"=>	\DEBUG?$e->getMessage():"Something Went wrong",
					]));
				}
			break;
			case 'deactivate':
				\ENVIRONMENT::deactivate($_REQUEST["environment"]);
				header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel."?".http_build_query([ "page"	=>	"install" ]));
			break;
			case 'change-credentials':
				try {
					$config = \ENVIRONMENT::configuration();
					if( $_REQUEST["username"] == $config["settings"]["username"] && md5($_REQUEST["password"]) == $config["settings"]["password"] ){
						$config["settings"]["username"] = isset($_REQUEST["new-username"]) && $_REQUEST["new-username"]!=""?$_REQUEST["new-username"]:$config["settings"]["username"];
						$config["settings"]["password"] = isset($_REQUEST["new-password"]) && $_REQUEST["new-password"]!=""?md5($_REQUEST["new-password"]):$config["settings"]["password"];
						$config["settings"]["url"] = isset($_REQUEST["url"]) && $_REQUEST["url"]!=""?$_REQUEST["url"]:$config["settings"]["url"];

						if($_REQUEST["confirm-password"] == $_REQUEST["new-password"]){
							BOOT::save_credentials([
								"username"	=>	$config["settings"]["username"],
								"password"	=>	$config["settings"]["password"],
								"url"		=>	$config["settings"]["url"],
								"encrypt"	=>	false
							]);
							header("Location: ".\ENVIRONMENT::url()->root().$config["settings"]["url"]."/"."?".http_build_query([ "page"	=>	"logout" ]));
						} else throw new \InvalidArgumentException("New Password and Confirm Password did not match", 1);
					}
					else throw new \InvalidArgumentException("Invalid Username or Password", 1);
				} catch (\InvalidArgumentException $e) {
					header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel."?".http_build_query([
						"page"	=>	"credentials",
						"error"	=>	$e->getMessage(),
					]));
				}
				catch (\Exception $e) {
					header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel."?".http_build_query([
						"page"	=>	"credentials",
						"error"	=>	\DEBUG?$e->getMessage():"Something Went Wrong",
					]));
				}
			break;
		}
		die();
	} else {
		switch ($_REQUEST["environment-ajax-action"]) {
			case 'login':
				try {
					if(!isset($_REQUEST["username"]) || !isset($_REQUEST["password"])) throw new \Exception("Please fill all the details", 1);
					if(BOOT::login([
						"username"	=>	$_REQUEST["username"],
						"password"	=>	$_REQUEST["password"],
					]))
						header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel);
					else throw new \Exception("Invalid Credentials", 1);
				} catch (\Exception $e) {
					header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel."?".http_build_query([
						"error"	=>	$e->getMessage()
					]));
				}
				die();
			break;
			case 'first-boot':
				$request = [
					"error"	=>	"",
				];
				try {
					$_REQUEST["username"] = isset($_REQUEST["username"])?$_REQUEST["username"]:"";
					$_REQUEST["password"] = isset($_REQUEST["password"])?$_REQUEST["password"]:"";
					$_REQUEST["confirm-password"] = isset($_REQUEST["confirm-password"])?$_REQUEST["confirm-password"]:"";
					$_REQUEST["url"] = isset($_REQUEST["url"])?$_REQUEST["url"]:"";

					if($_REQUEST["username"]=="") $request["error"] .= "Username Not Given!<br>";
					if($_REQUEST["password"]=="") $request["error"] .= "Password Not Given!<br>";
					if($_REQUEST["confirm-password"]=="") $request["error"] .= "Confirm Password Not Given!<br>";
					if($_REQUEST["url"]=="") $request["error"] .= "Settings Url Not Given!<br>";

					if($_REQUEST["password"]!=$_REQUEST["confirm-password"]) $request["error"] .= "Password did not match!<br>";

					$request["username"] = $_REQUEST["username"];
					$request["password"] = $_REQUEST["password"];
					$request["confirm-password"] = $_REQUEST["confirm-password"];
					$request["url"] = $_REQUEST["url"];
					if($request["error"]==""){
						BOOT::save_credentials([
							"username"	=>	$_REQUEST["username"],
							"password"	=>	$_REQUEST["password"],
							"url"	=>	$_REQUEST["url"],
						]);
						header("Location: ".\ENVIRONMENT::url()->root().$_REQUEST["url"]);
					}else header("Location: ".\ENVIRONMENT::url()->root()."?".http_build_query($request));
				}
				catch (\maple\environment\exceptions\UrlAlreadyRegisteredException $e){
					header("Location: ".\ENVIRONMENT::url()->root()."?".http_build_query([ "error" => "Please select a different url" ]));
				}
				catch (\Exception $e) {
					header("Location: ".\ENVIRONMENT::url()->root()."?".http_build_query([ "error" => \DEBUG?$e->getMessage():"Something Went terribly wrong!" ]));
				}
				die();
			break;
			default:
				header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel);
				break;
		}
	};
}
$file = str_replace(\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel,"",\ENVIRONMENT::url()->current());
if($file == ""){
	if(BOOT::is_first()){
		require_once "first-boot.php";
		die();
	} else {
		session_start();
		if(BOOT::loggedin()){ require_once "settings.php"; }
		else require_once "login.php";
	}
}
else if($file){
	if(file_exists(__DIR__."/{$file}") && !is_dir(__DIR__."/{$file}") ){
		header("Content-Type: ".FILE::mime_type( __DIR__."/{$file}"));
		readfile(__DIR__."/{$file}");
	} else http_response_code(404);
}
else{ header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel); }
?>
