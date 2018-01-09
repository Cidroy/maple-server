<?php

if(isset($_REQUEST["app-ajax-action"])){
	session_start();
	require_once __DIR__."/setup/class-setup.php";
	switch ($_REQUEST["app-ajax-action"]) {
		case 'login':
				$_REQUEST["username"] = isset($_REQUEST["username"])?$_REQUEST["username"]:"";
				$_REQUEST["password"] = isset($_REQUEST["password"])?$_REQUEST["password"]:"";
				if(\maple\app\SETUP::login([ "username"	=>	$_REQUEST["username"], "password"	=>	$_REQUEST["password"], ])) header("Location: ".\maple\environments\eAPP::url("settings"));
				else header("Location: ".\maple\environments\eAPP::url("settings")."?".http_build_query([ "error"	=>	"Invalid Username or Password" ]));
				break;
		case 'not-loggedin':
				header("Location: ".\maple\environments\eAPP::url("settings"));
				break;
	}
	die();
}

$file = str_replace(\maple\environments\eAPP::url("settings"),"",ENVIRONMENT::url()->current());
if($file === ""){
	session_start();
	require_once __DIR__."/setup/class-setup.php";
	if(isset($_SESSION["app-environment"]) && isset($_SESSION["app-environment"]["active"]) ) require_once __DIR__.'/pages/index.php';
	else require_once __DIR__.'/pages/login.php';
}
else if(file_exists(__DIR__."/{$file}") && !is_dir(__DIR__."/{$file}") ){
	header("Content-Type: ".maple\environments\FILE::mime_type( __DIR__."/{$file}"));
	readfile(__DIR__."/{$file}");
} else http_response_code(404);
?>
