<?php
namespace maple\cms;
use \__MAPLE__;
use \maple\environments\eMAPLE;
if(\ENVIRONMENT::url()->matches(eMAPLE::install_url)){
	/**
	 * Setup Handler
	 * @since 1.0
	 * @package Maple CMS
	 * @author Rubixcode
	 */
	class SETUP{
		const tables = [
			"users"	=>	[
				"id"		=>	[ "primary" => true, "auto-increment"	=>	true, "type" =>	"int", ],
				"name"		=>	[ "type" => "varchar", "length" => 50, ],
				"username"	=>	[ "type" => "varchar", "length" => 50, "unique"	=> "true", ],
				"email"		=>	[ "type" => "varchar", "length" => 50, "unique"	=> "true", ],
				"password"	=>	[ "type" => "text", ],
				"registered"=>	[ "type" => "datetime", ],
				"access"	=>	[ "type" => "int", "default" => 0 ],
				"permissions"=>	[ "type" => "text",],
			],
		];

		/**
		 * Init Setup
		 */
		public static function initialize(){
			if(!SESSION::active())	SESSION::start();
			FILE::remove(\CONFIG);
			FILE::remove(\CACHE);
			$_SESSION["maple/cms"] = isset($_SESSION["maple/cms"])?$_SESSION["maple/cms"]:[];
			echo isset($_REQUEST["cms-ajax-action"])?
				self::ajax_handler($_REQUEST["cms-ajax-action"],$_REQUEST):
				self::show_page();
			$_SESSION["maple/cms"]["setup/messages"] = [];
			unset($_REQUEST["cms-ajax-action"]);
		}

		/**
		 * Ajax Handling
		 * @param string $task ajax task
		 * @return string response
		 */
		private static function ajax_handler($task,$param){
			switch ($task) {
				case 'install-database':
					if(array_diff(["db-type","db-name","db-server","db-username","db-password","db-confirm-password",],array_keys($param)))
						$_SESSION["maple/cms"]["setup/messages"][]	= "Insufficient Details Given";
					else if(!in_array($param["db-type"],["mysql"])) $_SESSION["maple/cms"]["setup/messages"][]	= "Incompatible database";
					else if($param["db-confirm-password"]!==$param["db-password"]) $_SESSION["maple/cms"]["setup/messages"][] = "Password did not match";
					else try {
						$param["prefix"] = isset($param["prefix"])?$param["prefix"]:"";
						$db_param = [
							"database_name"	=>	$param["db-name"],
							"database_type"	=>	$param["db-type"],
							"server"		=>	$param["db-server"],
							"username"		=>	$param["db-username"],
							"password"		=>	$param["db-password"],
							"prefix"		=>	$param["db-prefix"]
						];
						if(isset($_SESSION["maple/cms"]["storage"]["database"])) $db_param = $_SESSION["maple/cms"]["storage"]["database"];
						$db = DB::object($db_param);
						$_SESSION["maple/cms"]["storage"] = isset($_SESSION["maple/cms"]["storage"])?$_SESSION["maple/cms"]["storage"]:[];
						$_SESSION["maple/cms"]["storage"]["database"] = $db_param;
						header("Location: ".\ENVIRONMENT::url()->root(eMAPLE::install_url."/")); die();
					} catch (\Exception $e) { $_SESSION["maple/cms"]["setup/messages"][] = "Unable to use database with these settings.\n Server says : '{$e->getMessage()}' <br> ".(string)$e ; }
					try {
						unset($_REQUEST["db-password"]);
						unset($_REQUEST["db-confirm-password"]);
					} catch (\Exception $e) { }
					return self::render_page("install-database");
				break;
				case 'setup-admin':
					if(array_diff(["admin-email","admin-dashboard","admin-username","admin-password","admin-confirm-password",],array_keys($param)))
						$_SESSION["maple/cms"]["setup/messages"][]	= "All fields are mandetory";
					else if($param["admin-confirm-password"]!==$param["admin-password"]) $_SESSION["maple/cms"]["setup/messages"][] = "Password did not match";
					else try {
						$db_param = $_SESSION["maple/cms"]["storage"]["database"];
						try{
							$db = DB::object($db_param);
							if(!$db->table_exists("users")) $db = null;
						}catch(\Exception $e){}
						if(($db && !$db->count("users",[ "OR" => [ "username" => $param["admin-username"], "email" => $param["admin-email"] ] ])) || !$db){
							$_SESSION["maple/cms"]["storage"]["administrator"] = [
								"username"	=>	$param["admin-username"],
								"email"		=>	$param["admin-email"],
								"password"	=>	$param["admin-password"],
								"dashboard"	=>	"/".trim(rtrim(str_replace(URL::http("%ROOT%"),"",explode("?",$param["admin-dashboard"])[0]),"/"),"/"),
							];
							header("Location: ".\ENVIRONMENT::url()->root(eMAPLE::install_url."/")); die();
						} else { $_SESSION["maple/cms"]["setup/messages"][]	= "Username or Email Already Exists"; }
					} catch (\Exception $e) {  $_SESSION["maple/cms"]["setup/messages"][] = "Unable to use database with these settings.\n Server says : '{$e->getMessage()}' <br> ".(string)$e;  }
					return self::render_page("setup-admin");
				break;
				case 'confirm':
					try {
						$db_param = $_SESSION["maple/cms"]["storage"]["database"];
						$admin	= $_SESSION["maple/cms"]["storage"]["administrator"];
						$db = DB::object($db_param);
						DB::initialize($db);
						$actives = file_exists(PLUGIN::active_file)?json_decode(file_get_contents(PLUGIN::active_file),true):[];
						SECURITY::initialize();
						\ENVIRONMENT::lock("maple/cms : setup");

						FILE::remove(PLUGIN::config_location);
						PLUGIN::initialize();

						foreach (FILE::get_folders(ROOT.__MAPLE__."/plugins") as $plugin){
							if(!file_exists($plugin."/package.json")) continue;
							$buffer = json_decode(file_get_contents($plugin."/package.json"),true);
							self::install__permission($buffer["namespace"],$plugin);
						}

						$dbs = new \maple\cms\database\Schema();
						foreach (self::tables as $table => $schema) {
							if(!$dbs->table_exists($table)){
								$dbs->create($table);
								foreach($schema as $column => $attributes) $dbs->add_column($column,$attributes);
							}
							$dbs->save();
						}
						if(!$db->count("users",[
							"OR"	=>	[
								"username"	=>	$admin["username"],
								"email"	=>	$admin["email"],
							],
						]))
						$db->insert("users",[
							"username"	=>	$admin["username"],
							"email"		=>	$admin["email"],
							"password"	=>	md5($admin["password"]),
							"access"	=>	SECURITY::get_user_group_code("administrator"),
							"#registered"=> "NOW()",
							"permissions"=>	json_encode(SECURITY::default_user_permission)
						]);

						USER::login($admin["username"],$admin["password"]);

						foreach (FILE::get_folders(ROOT.__MAPLE__."/plugins") as $plugin) {
							if(!file_exists($plugin."/package.json")) continue;
							$buffer = json_decode(file_get_contents($plugin."/package.json"),true);
							PLUGIN::activate($buffer["namespace"],[
								"sources"	=> [ROOT.__MAPLE__."/plugins"],
								"install"	=> true
							]);
						}

						$shortcode = new SHORTCODE("dashboard");
						if(!PAGE::add([
							"name"	=>	"dashboard",
							"url"	=>	$admin["dashboard"],
							"title"	=>	"Dashboard",
							"content"=> (string)$shortcode
						]))
						$admin["dashboard"] = PAGE::get("name","dashboard")["url"];
						file_put_contents(ROOT.__MAPLE__."/configurations.php",TEMPLATE::render_file(__DIR__."/assets/configurations.php",["database" => $_SESSION["maple/cms"]["storage"]["database"]]));
						USER::logout();
						\ENVIRONMENT::unlock();
						header("Location: ".\ENVIRONMENT::url()->root($admin["dashboard"]."/")); die();
					} catch (\Exception $e){
						$_SESSION["maple/cms"]["setup/messages"][]	= json_encode($e);
					}

					USER::logout();
					header("Location: ".\ENVIRONMENT::url()->root(eMAPLE::install_url."/")); die();
				break;
				case 'cancel':
					unset($_SESSION["maple/cms"]["storage"]);
					header("Location: ".\ENVIRONMENT::url()->root(eMAPLE::install_url."/")); die();
				break;
				default:
				break;
			}
		}

		/**
		 * Autohandle and Show HTML Page for specific tasks
		 * @return string response
		 */
		private static function show_page(){
			if(!file_exists(ROOT.__MAPLE__."/configurations.php") && !isset($_SESSION["maple/cms"]["storage"]["database"]) ) return self::render_page("install-database");
			else if(!isset($_SESSION["maple/cms"]["storage"]["administrator"])) return self::render_page("setup-admin");
			else return self::render_page("confirm");
		}

		/**
		 * Return specific install page
		 * @param  string $name page-name
		 * @param  array  $more render attributes
		 * @return string       rendered html
		 */
		private static function render_page($name,$more = []){
			$basic_details = [
				"url"	=>	[
					"install"	=>	\ENVIRONMENT::url()->root(eMAPLE::install_url."/"),
					"root"		=>	\ENVIRONMENT::url()->root(),
					"request"	=>	$_REQUEST
				],
				"messages"	=>	isset($_SESSION["maple/cms"]["setup/messages"])?$_SESSION["maple/cms"]["setup/messages"]:[]
			];
			$basic_details = array_merge($basic_details,$more);
			switch ($name) {
				case 'install-database':
					return TEMPLATE::render_file(__DIR__."/pages/index.html",array_merge($basic_details,[
						"content"	=>	[
							"title"	=>	"Database",
							"body"	=>	TEMPLATE::render_file(__DIR__."/pages/install-database.html",$basic_details)
						],
					]));
				break;
				case 'setup-admin':
					return TEMPLATE::render_file(__DIR__."/pages/index.html",array_merge($basic_details,[
						"content"	=>	[
							"title"	=>	"Database",
							"body"	=>	TEMPLATE::render_file(__DIR__."/pages/setup-admin.html",$basic_details)
						],
					]));
				break;
				case 'confirm':
					return TEMPLATE::render_file(__DIR__."/pages/index.html",array_merge($basic_details,[
						"content"	=>	[
							"title"	=>	"Database",
							"body"	=>	TEMPLATE::render_file(__DIR__."/pages/confirm.html",array_merge($basic_details,[
								"database"		=>	array_merge($_SESSION["maple/cms"]["storage"]["database"],["password"=>"******"]),
								"administrator"	=>	array_merge($_SESSION["maple/cms"]["storage"]["administrator"],["password"=>"******"]),
							]))
						],
					]));
				break;
			}
			return false;
		}

		/**
		 * Install permission from folder
		 * @uses \maple\cms\SECURITY::str_to_permitted_groupcodes
		 * @param  string $namespace default namespace
		 * @param  string $folder    plugin folder path
		 */
		private static function install__permission($namespace,$folder){
			if(!file_exists("{$folder}/permissions.json")) return;
			$user_codes=json_decode(file_get_contents(SECURITY::_permission_location."/user-type.json"),true);
			$user_codes = array_flip($user_codes);
			foreach ($user_codes as $key => $value) $user_codes[$key] = json_decode(file_get_contents(SECURITY::_permission_location."/{$key}.json"),true);
			$plugin_permissions = json_decode(file_get_contents("{$folder}/permissions.json"),true);
			foreach ($plugin_permissions as $permission) {
				$permission["namespace"] = isset($permission["namespace"])?$permission["namespace"]:$namespace;
				$permission["access"] = SECURITY::str_to_permitted_groupcodes($permission["access"]);
				foreach ($permission["access"] as $group) {
					if(!isset($user_codes[$group][$permission["namespace"]])) $user_codes[$group][$permission["namespace"]] = [];
					if(!in_array($permission["name"],$user_codes[$group][$permission["namespace"]])) $user_codes[$group][$permission["namespace"]][] = $permission["name"];
				}
			}
			foreach ($user_codes as $key => $value) file_put_contents(SECURITY::_permission_location."/{$key}.json",json_encode($value));
		}
	}

}
?>
