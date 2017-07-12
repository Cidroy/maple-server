<?php
	namespace maple\app;

	class SETUP{
		public static function save($param){
			$missing = array_diff(["username","password","web-root","url"],array_keys($param));
			if($missing) throw new Exception("Insufficent parameters passed to ".__METHOD__.", missing parameters".implode(",",$missing), 1);

			$ret = [];
			$_web_root	= $param["web-root"];
			$_url 		= $param["url"];
			if(!\ENVIRONMENT::url()->available("maple/app",$_web_root)) 	$ret = array_merge($_REQUEST,[ "type" => "error", "message"	=> "Url Conflict! Please try a different url for Environment App Url" ]);
			else if(!\ENVIRONMENT::url()->available("maple/app",$_url)) 	$ret = array_merge($_REQUEST,[ "type" => "error", "message"	=> "Url Conflict! Please try a different url for Environment App Settings Url" ]);
			if($ret)	return $ret;
			$config = \maple\environments\eAPP::configuration();
			if(strrpos($_web_root,"/") != 0) $_url = "/{$_web_root}";
			if(strrpos($_url,"/") != 0) $_url = "/{$_url}";
			$config["web-root"] = $param["web-root"];
			$config["credentials"] = [
				"url"		=>	$param["url"],
				"username" 	=>	$param["username"],
				"password"	=>	md5($param["password"]),
			];
			if(isset($param["encrypt"]) && !$param["encrypt"]) $config["credentials"]["password"] = $param["password"];
			\ENVIRONMENT::lock("maple/app : setup");
				file_put_contents(\maple\environments\eAPP::configuration_location(),json_encode($config,JSON_PRETTY_PRINT));
				\ENVIRONMENT::url()->register("maple/app",$_web_root);
				\ENVIRONMENT::url()->register("maple/app",$_web_root.$_url);
			\ENVIRONMENT::unlock();
			return [ "type" => "success" ];
		}

		public static function login($param){
			if(!isset($param["username"])) throw new Exception("Insufficent Arguments to ".__METHOD__.", missing 'username' ", 1);
			else if(!isset($param["password"])) throw new Exception("Insufficent Arguments to ".__METHOD__.", missing 'password' ", 1);

			$config = \maple\environments\eAPP::configuration();
			if(
				$param["username"] == $config["credentials"]["username"] &&
				md5($param["password"]) == $config["credentials"]["password"]
			){
				if (session_status() == PHP_SESSION_NONE) session_start();
				$_SESSION["app-environment"] = [ "active"	=>	true, ];
				return true;
			}
			else return false;
		}
	}
?>
