<?php
namespace maple\environments;
class eMAPLE implements iRenderEnvironment{
	private static $theme = [
		"loaded"	=>	false,
	];
	private static $content_dump = false;

	public static function initialize(){
		require_once __DIR__."/index.php";
		\TEMPLATE::Initialize();
		spl_autoload_register('MAPLE::Autoload');
		\MAPLE::add_autoloader('HTML',\ROOT.\INC.'class-html.php');
		\MAPLE::add_autoloader('SITE',\ROOT.\INC.'class-site.php');
		\MAPLE::add_autoloader('PARSER',\ROOT.\INC.'class-parser.php');
		\MAPLE::add_autoloader('CMS_PLUGIN',\ROOT.\INC.'cms/class-plugin.php');
		\MAPLE::add_autoloader('Maple\Cms\PAGE',\ROOT.\INC.'cms/class-page.php');

		\SESSION::start();
		try{
			\MAPLE::SetUser();
			\MAPLE::Initialize();
			\SESSION::refresh();
		}
		catch(Exception $e){ }

	}

	public static function load(){
		if(\ENVIRONMENT::is_allowed("maple-load")){
			self::initialize();
			// Start the Content Buffering here to stop any issues in flow
			ob_start();

			try{
				// TODO : Specialize for admin page too
				if(!\MAPLE::is_admin_page())		\MAPLE::Do_Autos();

				\FILE::safe_require_once(\ROOT.\INC.'preload.php');
				$t = \MAPLE::Parse(\MAPLE::GetPage(\URL::page(1)));
				if($t=='')	\MAPLE::AddHook(\MAPLE::Parse(\MAPLE::GetPage('')));
				else 		\MAPLE::AddHook($t);
				\UI::js()->add_src(\URL::http("%PLUGIN%maple/maple.js"));
			}
			catch(Exception $e){ }
		}
	}

	public static function execute(){
		if(\ENVIRONMENT::is_allowed("maple-load") && \ENVIRONMENT::is_allowed("maple-execute")){
			self::load_theme();
			\SECURITY::has_access('maple-dashboard') && \URL::http("%ADMIN%") != \URL::http("%CURRENT%")?\UI::navbar()->add_button('<i class="material-icons left">dashboard</i>Dashboard',\URL::http("%ADMIN%")):false;
			\MAPLE::ResolveHooks();
			$render_param = [
				"settings"	=>	[
					"language"	=>	\UI::language()->get(),
				],
				"content"=>	\UI::content()->resolve(),
				"filter"=>	[
					"body_start_content"	=>	\MAPLE::do_filters("body_start_content"),
					"pre_nav_content"		=>	\MAPLE::do_filters("pre_nav_content"),
					"admin_sidebar_list"	=>	\MAPLE::do_filters("admin_sidebar_list"),
					"sidebar_list"			=>	\MAPLE::do_filters("sidebar_list"),
					"post_nav_content"		=>	\MAPLE::do_filters("post_nav_content"),
					"pre_footer_content"	=>	\MAPLE::do_filters("pre_footer_content"),
					"post_footer_content"	=>	\MAPLE::do_filters("post_footer_content"),
					"body_end_content"		=>	\MAPLE::do_filters("body_end_content").\ERROR::ShowDebugBar(),
				],
				"html"	=>	[
					"head"	=>	\UI::header()->get_script(),
					"foot"	=>	\UI::footer()->get_script(),
					"css"	=>	[
						"src"	=>	\UI::css()->src(),
						"script"=>	\UI::css()->get_script(),
					],
					"js"	=>	[
						"src"	=>	\UI::js()->src(),
						"script"=>	\UI::js()->get_script(),
					],
					"title"	=>	implode(" - ",\UI::title()->get()),
					"navbar"=>	[
						"buttons"	=>	\UI::navbar()->buttons(),
						"html"		=>	\UI::navbar()->html(),
						"sidebar"	=>	[
							"content"	=>	"",
							// "background"=>	"",
						],
					],
				],
				"site"	=>	[
					"name"	=>	\SITE::Name(),
					"owner" =>	[
						"name"	=>	"Team Rubixcode",
						"link"	=>	"http://rubixcode.com",
					]
				],
				"color"=>	[
					"primary"	=>	"indigo",
					"background"=>	"white",
					"text"		=>	"black",
					"footer"	=>	"grey darken-4"
				]
			];
			$content = self::theme("renderContent",$render_param);
			if($content["type"] == "success"){
				$content = $content["content"];
				$head = self::theme("renderHead",$render_param);
				$footer = self::theme("renderFooter",$render_param);
				self::$content_dump = $head.$content.$footer;
				return 0;
			}
			return $content["error"];
		}
	}

	public static function direct(){
		if( isset($_REQUEST["maple_ajax"]) && isset($_REQUEST["maple_ajax_action"]) ){
			self::initialize();
			\FILE::safe_require_once(\ROOT.\INC.'ajax_handler.php');
			return true;
		}
		return false;
	}

	public static function has_content(){ return \MAPLE::$content; }

	public static function content(){ return self::$content_dump; }

	public static function error($param){
		self::load_theme();
		return self::theme("renderError",$param);
	}

	public static function theme($function,$param = []){
		return call_user_func(self::$theme["class"]."::{$function}",$param);
	}

	public static function load_theme(){
		if(self::$theme["loaded"] === true) return;
		require_once(\ROOT.\INC."cms/interface-theme.php");
		self::$theme["namespace"] = \MAPLE::GetOption("theme");
		$temp = json_decode(\FILE::read(\ROOT.\THEME."themes.json"),true);
		if(!isset($temp[self::$theme["namespace"]])){
			\MAPLE::DashMessage([
				"type"	=>	"error",
				"title"	=>	"Invalid Theme ".self::$theme["namespace"],
				"message" => "please Change the Theme settings",
				"permission" =>	"maple-theme-change"
			]);
			$temp = end($temp);
		} else $temp = $temp[self::$theme["namespace"]];
		self::$theme = array_merge($temp,self::$theme);
		require_once(\ROOT.\THEME.self::$theme["load"]);
		self::$theme["loaded"] == true;
	}
}
?>
