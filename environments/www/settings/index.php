<?php

	if(isset($_REQUEST["www-ajax-action"])){
		session_start();
		require_once __DIR__."/setup/class-setup.php";
		switch ($_REQUEST["www-ajax-action"]) {
			case 'login':
					$_REQUEST["username"] = isset($_REQUEST["username"])?$_REQUEST["username"]:"";
					$_REQUEST["password"] = isset($_REQUEST["password"])?$_REQUEST["password"]:"";
					if(\maple\www\SETUP::login([ "username"	=>	$_REQUEST["username"], "password"	=>	$_REQUEST["password"], ])) header("Location: ".\maple\environments\eWWW::url("settings"));
					else header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query([ "error"	=>	"Invalid Username or Password" ]));
					die();
					break;
			case 'change-credentials':
					if( isset($_SESSION["www-environment"]) && $_SESSION["www-environment"]["active"]){
						$config = \maple\www\WWW::configuration();
						if( $_REQUEST["username"] == $config["credentials"]["username"] && md5($_REQUEST["password"]) == $config["credentials"]["password"] ){
							$config["credentials"]["username"] = isset($_REQUEST["new-username"]) && $_REQUEST["new-username"]!=""?$_REQUEST["new-username"]:$config["credentials"]["username"];
							$config["credentials"]["password"] = isset($_REQUEST["new-password"]) && $_REQUEST["new-password"]!=""?md5($_REQUEST["new-password"]):$config["credentials"]["password"];
							$config["credentials"]["url"] = isset($_REQUEST["url"]) && $_REQUEST["url"]!=""?$_REQUEST["url"]:$config["credentials"]["url"];

							if($_REQUEST["confirm-password"] == $_REQUEST["new-password"]){
								try {
									\maple\www\SETUP::save([
										"username"	=>	$config["credentials"]["username"],
										"password"	=>	$config["credentials"]["password"],
										"url"		=>	$config["credentials"]["url"]."/",
										"encrypt"	=>	false
									]);
									header("Location: ".\ENVIRONMENT::url()->root().$config["credentials"]["url"]."/"."?".http_build_query([ "page"	=>	"logout" ]));
								} catch (Exception $e) {
									header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query([
										"error"	=>	$e->getMessage(),
										"page"	=>	"credentials"
									]));
								}
							} else header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query([
								"error"	=>	"New Password did not match",
								"page"	=>	"credentials"
							]));
						}
						else header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query([
							"error"	=>	"Invalid Username or Password",
							"page"	=>	"credentials"
						]));
						break;
					}
			case 'activate-site':
					if( isset($_SESSION["www-environment"]) && $_SESSION["www-environment"]["active"]){
						try {
							if(\maple\www\SITE::activate([
								"location"	=>	$_REQUEST["location"],
								"url"	=>	$_REQUEST["url"],
							])) header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query(["page"=>"configure"]));
						} catch (Exception $e) {
							header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query(["page"=>"configure","error"=>$e->getMessage()]));
						}
						break;
					}
			case 'edit-site':
					if( isset($_SESSION["www-environment"]) && $_SESSION["www-environment"]["active"]){
						try {
							if(\maple\www\SITE::edit([
								"location"	=>	$_REQUEST["location"],
								"url"	=>	$_REQUEST["url"],
							])) header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query(["page"=>"configure"]));
						} catch (Exception $e) {
							header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query(["page"=>"configure","error"=>$e->getMessage()]));
						}
						break;
					}
			case 'disable-site':
					if( isset($_SESSION["www-environment"]) && $_SESSION["www-environment"]["active"]){
						try {
							if(\maple\www\SITE::disable([
								"location"	=>	$_REQUEST["location"],
								"url"	=>	"",
							])) header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query(["page"=>"configure"]));
						} catch (Exception $e) {
							header("Location: ".\maple\environments\eWWW::url("settings")."?".http_build_query(["page"=>"configure","error"=>$e->getMessage()]));
						}
						break;
					}
			case 'not-loggedin':
					header("Location: ".\maple\environments\eWWW::url("settings"));
					break;

		}
		die();
	}

	$file = str_replace(\maple\environments\eWWW::url("settings"),"",ENVIRONMENT::url()->current());

	if($file === ""){
		session_start();
		require_once __DIR__."/setup/class-setup.php";
		if(isset($_SESSION["www-environment"]) && isset($_SESSION["www-environment"]["active"]) ) require_once __DIR__.'/pages/index.php';
		else require_once __DIR__."/pages/login.php";
		die();
	} else if(file_exists(__DIR__."/{$file}") && !is_dir(__DIR__."/{$file}") ){
		header("Content-Type: ".maple\environments\FILE::mime_type( __DIR__."/{$file}"));
		readfile(__DIR__."/{$file}");
	} else http_response_code(404);
?>
