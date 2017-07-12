<?php
	require_once __DIR__."/class-setup.php";

	if(isset($_REQUEST["www-ajax-action"])){
		switch ($_REQUEST["www-ajax-action"]) {
			case 'install':
				try {
					if($_REQUEST["password"]!=$_REQUEST["confirm-password"])
						throw new Exception("Passowrd did not match", 1);
					else {
						\maple\www\SETUP::save([
							"username"	=>	$_REQUEST["username"],
							"password"	=>	$_REQUEST["password"],
							"url"		=>	$_REQUEST["url"]."/",
						]);
						header("Location: ".ENVIRONMENT::url()->root().$_REQUEST["url"]."/");
					}
				} catch (Exception $e) {
					header("Location: ".ENVIRONMENT::url()->root(false)."/www/install/?".http_build_query([
						"error"	=>	$e->getMessage()
					]));
				}
				break;
		}
		die();
	}

	$file = str_replace(\maple\environments\eWWW::url("install"),"",ENVIRONMENT::url()->current());

	if($file === ""){ require_once __DIR__.'/home.php'; }
	else if(file_exists(__DIR__."/../{$file}") && !is_dir(__DIR__."/../{$file}") ){
		header("Content-Type: ".maple\environments\FILE::mime_type( __DIR__."/../{$file}"));
		readfile(__DIR__."/../{$file}");
	} else http_response_code(404);
?>
