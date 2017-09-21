<?php
namespace maple\cms\login;
use maple\cms\TEMPLATE;
use maple\cms\DB;
/**
 * Dashboard Class
 */
class DASHBOARD{

	public static function new_users(){
		$users = DB::_()->select("users","*",[
			"#registered[>]"	=>	"DATE_ADD(NOW(),INTERVAL -1 DAY)"
		]);
		\maple\cms\LOG::info($users);
		return [
			"icon"	=>	"apps",
			"content"=> TEMPLATE::render("maple/login","dashboards/new-user",[
				"users"	=>	$users
			]),
		];
	}
}

?>
