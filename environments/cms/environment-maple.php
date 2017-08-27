<?php
namespace maple\environments;
use \maple\cms\MAPLE;

class eMAPLE implements iRenderEnvironment{
	const cms_content = \ROOT."/cms-content";
	const install_url = "/maple/install";

	private static $content = null;
	private static $_initialized = false;
	private static $_initialized_good = false;
	private static $output 	= [
		"header"	=>	"",
		"content"	=>	"",
		"footer"	=>	"",
	];

	public static function initialize(){
		try{
			if(!self::$_initialized){
				\ENVIRONMENT::define("__MAPLE__",str_replace(\ROOT,"",__DIR__));
				\ENVIRONMENT::define('INC',\__MAPLE__.'/include');
				\ENVIRONMENT::define('LIBRARY',\__MAPLE__.'/library');
				\ENVIRONMENT::define('CONFIG',\__MAPLE__.'/configurations');
				\ENVIRONMENT::define('VENDOR',\__MAPLE__.'/vendors');
				\ENVIRONMENT::define('CACHE',\__MAPLE__.'/cache');
				\ENVIRONMENT::define('CONTENTS','/cms-content');
				\ENVIRONMENT::define('ADMIN',\__MAPLE__.'/admin');
				\ENVIRONMENT::define('DATA','data');
				\ENVIRONMENT::define('PLUGIN',\CONTENTS.'/plugin');
				\ENVIRONMENT::define('THEME',\CONTENTS.'/themes');

				\ENVIRONMENT::define('LOG',\__MAPLE__.'/~$Logs');

				if(!file_exists(\ROOT.\CACHE)) mkdir(\ROOT.\CACHE,0777,true);
				if(!file_exists(\ROOT.\CONFIG)) mkdir(\ROOT.\CONFIG,0777,true);

				require_once \ROOT.\INC.'/maple.php';
				spl_autoload_register("\\maple\\cms\\MAPLE::autoloader");

				$_primary_classes = [
					"maple\\cms\\exceptions"=> \ROOT.\INC.'/class-exceptions.php',
					"maple\\cms\\ERROR" 	=> \ROOT.\INC.'/class-error-handler.php',
					"maple\\cms\\DB" 		=> \ROOT.\INC.'/class-db.php',
					"maple\\cms\\URL" 		=> \ROOT.\INC.'/class-url.php',
					"maple\\cms\\SESSION" 	=> \ROOT.\INC.'/class-session.php',
					"maple\\cms\\SECURITY" 	=> \ROOT.\INC.'/class-security.php',
					"maple\\cms\\FILE" 		=> \ROOT.\INC.'/class-file.php',
					"maple\\cms\\SITE" 		=> \ROOT.\INC.'/class-site.php',
					"maple\\cms\\USER" 		=> \ROOT.\INC.'/class-user.php',
				];
				$_autoload = [
					"maple\\cms\\TEMPLATE"=> \ROOT.\INC.'/template/class-template.php',
					"maple\\cms\\CACHE"	=> \ROOT.\INC.'/class-cache.php',
					"maple\\cms\\ROUTER" 	=> \ROOT.\INC.'/class-route.php',
					"maple\\cms\\TIME" 	=> \ROOT.\INC.'/class-time.php',
					"maple\\cms\\PAGE" 	=> \ROOT.\INC.'/class-page.php',

					"maple\\cms\\NOTIFICATION" 		=> \ROOT.\INC.'/class-notification.php',
					"maple\\cms\\NOTIFICATION_STYLE" 	=> \ROOT.\INC.'/class-notification.php',

					"maple\\cms\\database\\Schema" 	=> \ROOT.\INC.'/database/class-schema.php',

					"maple\\cms\\PLUGIN" 		=> \ROOT.\INC.'/plugin/class-plugin.php',
					"maple\\cms\\SHORTCODE" 	=> \ROOT.\INC.'/plugin/class-shortcode.php',
					"maple\\cms\\THEME" 		=> \ROOT.\INC.'/theme/class-theme.php',
					"maple\\cms\\UI" 			=> \ROOT.\INC.'/theme/class-ui.php',
				];
				foreach ($_primary_classes as $class) require_once $class;
				foreach ($_autoload as $class => $dir) MAPLE::add_autoloader($class,$dir);
				self::$_initialized = true;
			}
			if(!self::$_initialized_good){
				\maple\cms\URL::initialize();
				\maple\cms\TEMPLATE::initialize();
				\maple\cms\ERROR::initialize();
				\maple\cms\DB::initialize();
				\maple\cms\SESSION::start();
				\maple\cms\USER::initialize();
				\maple\cms\SECURITY::initialize();
				\maple\cms\SITE::initialize();
				\maple\cms\CACHE::initialize();

				\maple\cms\PLUGIN::source(\ROOT.\__MAPLE__."/plugins");
				\maple\cms\PLUGIN::source(self::cms_content."/plugins");
				\maple\cms\THEME::source(\ROOT.\__MAPLE__."/themes");
				\maple\cms\THEME::source(self::cms_content."/themes");

				\maple\cms\URL::add("%THEME%",null,\__MAPLE__."/themes");
				\maple\cms\URL::add("%PLUGIN%",null,\__MAPLE__."/plugins");
				\maple\cms\URL::add("%ADMIN%",\maple\cms\URL::name("maple/cms","dashboard"),null);
				self::$_initialized_good = true;
			}
		}catch(\Exception $e){ throw $e; }
	}

	public static function load(){
		if(\ENVIRONMENT::is_allowed("maple-load")){
			try {
				self::initialize();
				\maple\cms\PLUGIN::load("*");
				MAPLE::initialize(\maple\cms\PLUGIN::get());
				\maple\cms\PLUGIN::clear();

				\maple\cms\ROUTER::sources(MAPLE::_get_router_sources(true));
				\maple\cms\ROUTER::initialize();
				\maple\cms\TEMPLATE::add_template_sources(MAPLE::_get_template_sources(true));
				\maple\cms\THEME::initialize();
				\maple\cms\TEMPLATE::set_render_defaults();
				if(!\maple\cms\PLUGIN::active("maple/cms")) throw new \Exception("Plugin not ready", 1);
			} catch (\Exception $e) { self::diagnostics(); }
		}
		// \maple\cms\Log::info(\maple\cms\URL::name("maple/login","page|login"));
	}

	public static function execute(){
		if(\ENVIRONMENT::is_allowed("maple-execute") && self::$_initialized_good){
			// BUG: move below to execute

			$page = \maple\cms\PAGE::get("url",\maple\cms\PAGE::identify(\maple\cms\URL::http("%CURRENT%")));
			if($page){
				MAPLE::hook("\\maple\\cms\\SHORTCODE::execute_all",$page["content"],300);
				\maple\cms\UI::title()->add($page["title"]);
			}
			MAPLE::hook("\\maple\\cms\\ROUTER::dispatch",[],250);

			self::$output["content"] = \maple\cms\THEME::render_content();
			self::$output["header"]  = \maple\cms\THEME::render_head();
			self::$output["footer"]  = \maple\cms\THEME::render_footer();
			if(MAPLE::has_content()){
				if(\maple\cms\TEMPLATE::configuration("render")) self::$content = self::$output["header"].self::$output["content"].self::$output["footer"];
				else self::$content = json_encode([self::$output["header"],self::$output["content"],self::$output["footer"]]);
			}
		}
	}

	public static function direct(){
		try {
			self::initialize();
			$servers = [
				"vendor"	=>	[
					"url"	=>	\maple\cms\URL::http("%VENDOR%"),
					"source"=>	\maple\cms\URL::dir("%ROOT%%VENDOR%"),
					"deny"	=>	[".*\.json",".*\.php"]
				],
				"plugin"	=>	[
					"url"	=>	\maple\cms\URL::http("%PLUGIN%"),
					"source"=>	\maple\cms\PLUGIN::sources(),
					"deny"	=>	[".*\.json",".*\.php"]
				],
				"theme"	=>	[
					"url"	=>	\maple\cms\URL::http("%THEME%"),
					"source"=>	\maple\cms\THEME::sources(),
					"deny"	=>	[".*\.json",".*\.php"]
				],
			];
			foreach ($servers as $server ) {
				$headers = [
					"Cache-Control"	=>	"max-age=604800, public",
				];
				$file = \ENVIRONMENT::serve($server,$headers);
				if($file){
					self::$content = file_get_contents($file);
					\ENVIRONMENT::headers($headers);
					return true;
				}
			}
		} catch (\Exception $e) { }
		return false;
	}

	public static function has_content(){ return self::$content?true:false; }

	public static function content(){ return self::$content; }

	public static function error($param){
		$param = [
			"html"	=>	self::$output,
			"error"	=>	$param
		];
		$output = \maple\cms\THEME::render_error($param);
		return $output;
	}

	/**
	 * Test for any form of error in the Maple CMS Environment
	 */
	public static function diagnostics(){
		// test if the maple cms content content is available
		if(!file_exists(self::cms_content)){
			mkdir(self::cms_content,0777,true);
			mkdir(self::cms_content."/plugins",0777,true);
			mkdir(self::cms_content."/themes",0777,true);
		}
		if(\ENVIRONMENT::url()->matches(self::install_url)) require_once \ROOT.\__MAPLE__."/setup/index.php";
	}

}

?>
