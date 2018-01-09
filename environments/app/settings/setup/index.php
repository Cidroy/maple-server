<?php
	require_once __DIR__."/class-setup.php";

	function _app_return_result($param) {
		header("Location: ".ENVIRONMENT::url()->root()."app/install/?".http_build_query($param));
		die();
	}

	if(isset($_REQUEST["app-ajax-action"])){
		switch ($_REQUEST["app-ajax-action"]) {
			case 'install':
				$password		= $_REQUEST["password"];
				$con_password	= $_REQUEST["confirm-password"];
				unset($_REQUEST["app-ajax-action"]); unset($_REQUEST["password"]); unset($_REQUEST["confirm-password"]);
				$ret = [];
				if($password != $con_password)					$ret = array_merge($_REQUEST,[ "type" => "error", "message"	=> "Password did not match" ]);
				else $ret = \maple\app\SETUP::save([
					"username"	=>	$_REQUEST["username"],
					"password"	=>	$password,
					"web-root"	=>	"/".$_REQUEST["web-root"],
					"url"		=>	"/".$_REQUEST["url"],
				]);
				if($ret["type"] == "error") _app_return_result($ret);
				else header("Location: ".ENVIRONMENT::url()->root(false)."/".$_REQUEST["web-root"]."/".$_REQUEST["url"]."/");
				break;
			default: break;
		}
	}

	$file = str_replace(\maple\environments\eAPP::url("install"),"",ENVIRONMENT::url()->current());
	if($file === ""){ require_once __DIR__.'/home.php'; }
	else if(file_exists(__DIR__."/../{$file}") && !is_dir(__DIR__."/../{$file}") ){
		header("Content-Type: ".maple\environments\FILE::mime_type( __DIR__."/../{$file}"));
		readfile(__DIR__."/../{$file}");
	} else http_response_code(404);
?>
