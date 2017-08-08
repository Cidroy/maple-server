<?php
namespace maple\environments;
use \maple\cms\MAPLE;

class eMAPLE implements iRenderEnvironment{
	const cms_content = \ROOT."cms-content";

	private static $content = null;
	private static $_initialized = false;

	public static function initialize(){
		if(self::$_initialized) return;
		\ENVIRONMENT::define("__MAPLE__",str_replace(\ROOT,"",__DIR__));
		\ENVIRONMENT::define('INC',\__MAPLE__.'/include');
		\ENVIRONMENT::define('LIBRARY',\__MAPLE__.'/library');
		\ENVIRONMENT::define('CONFIG',\__MAPLE__.'/configurations');
		\ENVIRONMENT::define('VENDOR',\__MAPLE__.'/vendors');
		\ENVIRONMENT::define('CACHE',\__MAPLE__.'/cache');
		\ENVIRONMENT::define('CONTENTS','/maple-cms-content');
		\ENVIRONMENT::define('ADMIN',\__MAPLE__.'/admin');
		\ENVIRONMENT::define('DATA','data');
		\ENVIRONMENT::define('PLUGIN',\CONTENTS.'/plugin');
		\ENVIRONMENT::define('THEME',\CONTENTS.'/themes');

		\ENVIRONMENT::define('LOG',\__MAPLE__.'/~$Logs');

		require_once \INC.'/maple.php';
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
			"maple\cms\TEMPLATE"=> \ROOT.\INC.'/template/class-template.php',
			"maple\cms\CACHE"	=> \ROOT.\INC.'/class-cache.php',
			"maple\cms\ROUTER" 	=> \ROOT.\INC.'/class-route.php',
			"maple\cms\TIME" 	=> \ROOT.\INC.'/class-time.php',
			"maple\cms\PAGE" 	=> \ROOT.\INC.'/class-page.php',

			"maple\cms\NOTIFICATION" 		=> \ROOT.\INC.'/class-notification.php',
			"maple\cms\NOTIFICATION_STYLE" 	=> \ROOT.\INC.'/class-notification.php',

			"maple\cms\PLUGIN" 		=> \ROOT.\INC.'/plugin/class-plugin.php',
			"maple\cms\SHORTCODE" 	=> \ROOT.\INC.'/plugin/class-shortcode.php',
			"maple\cms\THEME" 		=> \ROOT.\INC.'/theme/class-theme.php',
			"maple\cms\UI" 			=> \ROOT.\INC.'/theme/class-ui.php',
		];
		foreach ($_primary_classes as $class) require_once $class;
		foreach ($_autoload as $class => $dir) MAPLE::add_autoloader($class,$dir);

		self::$_initialized = true;
		\maple\cms\ERROR::initialize();
		\maple\cms\DB::initialize();
		\maple\cms\URL::initialize();
		\maple\cms\SESSION::start();
		\maple\cms\USER::initialize();
		\maple\cms\SECURITY::initialize();
		\maple\cms\SITE::initialize();

		\maple\cms\CACHE::initialize();

		\maple\cms\PLUGIN::source(\ROOT.\__MAPLE__."/plugins");
		\maple\cms\PLUGIN::source(self::cms_content."/plugins");
		\maple\cms\PLUGIN::load("*");
		MAPLE::initialize(\maple\cms\PLUGIN::get());
		\maple\cms\PLUGIN::clear();

		\maple\cms\ROUTER::sources(MAPLE::_get_router_sources(true));
		\maple\cms\ROUTER::initialize();

		\maple\cms\THEME::source(\ROOT.\__MAPLE__."/themes");
		\maple\cms\THEME::source(self::cms_content."/themes");
		\maple\cms\TEMPLATE::add_template_sources(MAPLE::_get_template_sources(true));
		\maple\cms\TEMPLATE::initialize();
	}

	public static function load(){
		if(\ENVIRONMENT::is_allowed("maple-load")){
			try {
				self::initialize();
				if(!\maple\cms\PLUGIN::active("maple/cms")) throw new \Exception("Plugin not ready", 1);
			} catch (\Exception $e) {
				self::diagnostics();
				throw $e;
			}
		}
	}

	public static function execute(){
		if(\ENVIRONMENT::is_allowed("maple-execute")){
			// BUG: move below to execute
			/**
			* Add dashboard path to navbar
			* @permission maple/cms:dashboard
			* BUG: move to admin handler
			*/
			if(\maple\cms\SECURITY::permission("maple/cms","dashboard") && \maple\cms\URL::http("%CURRENT%")!=\maple\cms\URL::http("%ADMIN%"))
				\maple\cms\UI::navbar()->add_link("Dashboard",\maple\cms\URL::http("%ADMIN%"),\maple\cms\UI::icon("dashboard"));

			$page = \maple\cms\PAGE::get("url",\maple\cms\PAGE::identify(\maple\cms\URL::http("%CURRENT%")));
			if($page){
				MAPLE::hook("\\maple\\cms\\SHORTCODE::execute_all",$page["content"],300);
				\maple\cms\UI::title()->add($page["title"]);
			}
			MAPLE::hook("\\maple\\cms\\ROUTER::dispatch",[],250);

			$content = \maple\cms\THEME::render_content();
			$head 	 = \maple\cms\THEME::render_content();
			$footer  = \maple\cms\THEME::render_content();
			self::$content = $head.$content.$footer;
		}
	}

	public static function direct(){
		try {
			self::initialize();
			$servers = [
				"vendor"	=>	[
					"url"	=>	str_replace(\maple\cms\URL::http("%ROOT%"),"",\maple\cms\URL::http("%VENDOR%")),
					"source"=>	\maple\cms\URL::dir("%VENDOR%"),
					"deny"	=>	[".*\.json",".*\.php"]
				],
				"plugin"	=>	[
					"url"	=>	str_replace(\maple\cms\URL::http("%ROOT%"),"",\maple\cms\URL::http("%PLUGIN%")),
					"source"=>	\maple\cms\PLUGIN::sources(),
					"deny"	=>	[".*\.json",".*\.php"]
				],
				"theme"	=>	[
					"url"	=>	str_replace(\maple\cms\URL::http("%ROOT%"),"",\maple\cms\URL::http("%THEME%")),
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
					return true;
				}
			}
		} catch (\Exception $e) { }
		return false;
	}

	public static function has_content(){ return self::$content?true:false; }

	public static function content(){ return self::$content; }

	public static function error($param){ return \maple\cms\THEME::render_error($param); }

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
		// Test if database connection exists
		if(!file_exists(\__MAPLE__."/configurations.php")){

		}
		echo "string";
	}

}

?>
