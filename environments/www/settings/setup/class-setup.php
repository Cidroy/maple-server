<?php
	namespace maple\www;

	class SETUP{
		public static function save($param){
			$config = WWW::configuration();
			$buffer = $config;
			if(!$config["credentials"]){
				$missing = ["url","username","password"];
				$missing = array_diff($missing,array_keys($param));
				if($missing) throw new Exception("Insufficent Parameters to method ".__METHOD__.", missing arguments ".implode(",",$missing), 1);
			}
			$config["credentials"] = [
				"url"		=>	$param["url"],
				"username" 	=>	$param["username"],
				"password"	=>	md5($param["password"]),
			];
			if(isset($param["encrypt"]) && !$param["encrypt"]) $config["credentials"]["password"] = $param["password"];
			if(!\ENVIRONMENT::url()->available("maple/www","/{$param["url"]}"))
				throw new Exception("Url Conflict! Cannot use url ".\ENVIRONMENT::url()->root(false)."/{$param["url"]} as it is already assigned. Please try some other URL.", 1);
			\ENVIRONMENT::lock("maple/www : save-login-credentials");
				file_put_contents(WWW::configuration_location(),json_encode($config,JSON_PRETTY_PRINT));
				if(isset($param["url"])){
					if($buffer["credentials"])	\ENVIRONMENT::url()->unregister("maple/www","/{$buffer["credentials"]["url"]}");
					\ENVIRONMENT::url()->register("maple/www","/{$param["url"]}");
				}
			\ENVIRONMENT::unlock();
		}

		public static function login($param){
			$config = WWW::configuration();
			if(
				$param["username"] == $config["credentials"]["username"] &&
				md5($param["password"]) == $config["credentials"]["password"]
			){
				if (session_status() == PHP_SESSION_NONE) session_start();
				$_SESSION["www-environment"] = [ "active"	=>	true, ];
				return true;
			}
			else return false;
		}
	}
?>
