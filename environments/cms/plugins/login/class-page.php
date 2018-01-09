<?php
namespace maple\cms\login;
use \maple\cms\LOGIN;
use \maple\cms\TEMPLATE;
use \maple\cms\SECURITY;
use \maple\cms\MAPLE;
use \maple\cms\UI;
use \maple\cms\DB;
use \maple\cms\URL;
use \maple\cms\LOG;
use \maple\cms\TIME;

/**
 * Pae Handler
 * @since 1.0
 * @package Maple CMS Login
 * @author Rubixcode
 */
class PAGE{
	/**
	 * View My Profile
	 * BUG : does nothing
	 * @router maple/login:page|profile
	 * @return string html
	 */
	public static function profile_view(){
		MAPLE::has_content(true);
		return TEMPLATE::render("maple/login","profile/home");
	}

	/**
	 * Forgot Password Page
	 * @router maple/login:page|forgot-password
	 * @return string html
	 */
	public static function forgot_password(){
		MAPLE::has_content(true);
		$errors = [
			[
				"type"		=>	"info",
				"message"	=>	"Please enter your E-Mail address. You will receive a link to create a new password via E-Mail."
			]
		];
		UI::js()->add_src(__DIR__."/assets/index.js");
		return TEMPLATE::render("maple/login","forgot-password",[
			"errors"	=>	$errors,
		]);
	}

	/**
	 * Dashboard Home Page
	 * @router maple/login/dashboard:dashboard
	 * @return string html
	 */
	public static function d_home(){
		MAPLE::has_content(true);
		$quick_actions = [
			[
				"title"	=>	"Add User",
				"icon"	=>	"user-add",
				"description"	=>	"Add a new User with a role",
				"link"	=>	URL::name("maple/login/dashboard","users|add"),
				"permissions"	=>	["maple/login"=>"user|add"]
			],
			[
				"title"	=>	"Add User Group",
				"icon"	=>	"group-add",
				"description"	=>	"Add a User Group and define permissions",
				"link"	=>	URL::name("maple/login/dashboard","users-group|add"),
				"permissions"	=>	["maple/login"=>"user-group|add"]
			],
			[
				"title"	=>	"Settings",
				"icon"	=>	"settings",
				"description"	=>	"General Settings",
				"link"	=>	URL::name("maple/login/dashboard","settings"),
				"permissions"	=>	["maple/cms"=>"dashboard"]
			],
		];
		$data = [];
		$data["new-users"] = array_merge([["Date","Users"]],self::_new_users(["joined"=>-10]));
		return TEMPLATE::render("maple/login","dashboard-home",[
			"image"	=>	[
				"cover"	=> URL::http(__DIR__."/assets/images/dashboard-cover.jpg",[ "maple-image" => "optimise", "optimise"	=> "auto", "quality" => 720 ])
			],
			"quick_actions"	=>	$quick_actions,
			"graphs"	=>	[
				[
					"title"	=>	"Recently Joined Users",
					"note"	=>	"Showing for last 10 days",
					"icon"	=>	"people",
					"size"	=>	["s12"],
					"content"=>	UI::graph([
						"type"	=>	"ColumnChart",
						"size"	=>	["height"=>"300px","width"=>"100%"],
						"data"	=>	[
							"values"	=>	$data["new-users"],
							"options"	=>	[
								'legend'=>	['position'=>'bottom'],
								'tooltip'=>	['isHtml'=>true],
								"hAxis"	=>	["format" => "short"]
							]
						]
					])
				]
			]
		]);
	}

	/**
	 * @router maple/login/dashboard:users|all
	 * @return string html
	 */
	public static function d_users_all(){
		MAPLE::has_content(true);
		$user_group_name = SECURITY::user_groups();
		$param = [
			"limit"	=>	25,
			"offset"=>	0,
			"type"	=>	"*"
		];
		$temp = DB::_()->select("users",["id","name","username","email","access(group)"]);
		$users = [];
		foreach ($temp as $row ) {
			$users[$row["id"]]	= $row;
		}
		return TEMPLATE::render("maple/login","dashboard-users-all",[
			"users"	=>	[
				"group_name"	=> $user_group_name,
				"header"		=> array_keys(current($users)),
				"data"			=> $users
			]
		]);
	}

	/**
	 * @router maple/login/dashboard:user|view
	 * @return string html
	 */
	public static function d_user_view(){
		MAPLE::has_content(true);
		$condition = [];
		$selectors = ["id","username","email"];
		if(!URL::has_request($selectors,"get","any")){
			URL::redirect(URL::name("maple/login/dashboard","users|all"));
			return;
		}
		if(isset($_REQUEST["id"])) $condition["id"] = $_REQUEST["id"];
		if(isset($_REQUEST["username"])) $condition["username"] = $_REQUEST["username"];
		if(isset($_REQUEST["email"])) $condition["email"] = $_REQUEST["email"];
		$user = current(DB::_()->select("users","*",$condition));
		return TEMPLATE::render("maple/login","dashboard-user-view",[
			"user" => $user,
			"user_group" => SECURITY::user_groups()
		]);
	}

	/**
	 * @router maple/login/dashboard:users|add
	 */
	public static function d_user_add(){
		MAPLE::has_content(true);
		return TEMPLATE::render("maple/login","dashboard-user-add",[ "form"	=>	[ "groups"	=>	SECURITY::user_groups() ] ]);
	}

	/**
	 * @router maple/login/dashboard:users-group|view
	 */
	public static function d_users_group_view(){
		MAPLE::has_content(true);
		$primary = array_flip(SECURITY::primary_user_groups);
		$groups = array_diff(SECURITY::user_groups(),$primary);
		return TEMPLATE::render("maple/login","dashboard-user-group=view",[
			"primary"	=>	$primary,
			"groups"	=>	$groups,
		]);
	}

	/**
	 * @router maple/login/dashboard:settings
	 */
	public static function d_settings(){
		MAPLE::has_content(true);
		return TEMPLATE::render("maple/login","dashboard-settings",[
			"groups"	=>	SECURITY::user_groups(),
			"register"	=>	LOGIN::settings("registraion|allowed"),
			"default_group"	=>	LOGIN::settings("new-user|default-group"),
		]);
	}

	/**
	 * New Users based on parameters
	 * @param  array $param selection choice
	 * @return array        list
	 */
	private static function _new_users($param){
		if(!is_array($param))	throw new \InvalidArgumentException( "Argument #1 must be of type 'array'");
		$where = [];
		if(!$param["joined"] || !is_string($param["joined"])) $param["joined"] = -10;

		$__d1 = (new TIME())->setTime(0,0,0);
		$__d2 = (new TIME())->setTime(0,0,0);
		if($param["joined"]>0) $__d2 = $__d2->modify("{$param["joined"]} DAY");
		else $__d1 = $__d1->modify("{$param["joined"]} DAY");
		$days = new \DatePeriod($__d1,new \DateInterval("P1D"),$__d2);
		$result = [];
		foreach ($days as $day ) $result[(string)($day->format("d M"))] = [(string)($day->format("d M")),0];

		$prefix = DB::_()->prefix();
		$query = "SELECT DISTINCT DATE(`registered`) AS `date`,COUNT(`id`) AS `users` FROM `{$prefix}users` GROUP BY `date` HAVING `date` > DATE_ADD(NOW(), INTERVAL {$param["joined"]} DAY) ORDER BY `date` DESC";
		foreach (DB::_()->query($query)->fetchAll() as $row) {
			$row["date"] = new TIME($row["date"]);
			$result[(string)($row["date"]->format("d M"))] = [(string)($row["date"]->format("d M")),(integer)$row["users"]];
		}
		return array_values($result);
	}
}

?>
