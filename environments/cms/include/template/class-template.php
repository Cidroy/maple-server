<?php
	namespace maple\cms;
	use \ROOT;
	use \INC;
	/*
	TODO : add request settings
		- maple render
			-	with/without template
			-	only content
		- execute with request param
		- change theme render tech ... use twig in functions
		 	- class THEME_USE implements _TEMPLATE_THEME_
		- render from file,string
		- move maple render functions to class CMS
	 */
	require_once ROOT.INC."/template/interface-render-engine.php";
	require_once ROOT.INC."/template/class-render-engine.php";
	require_once ROOT.INC."/theme/class-theme.php";

	/**
	* This is the template class that is mandatory to add any contents to the UI
	* @since 1.0
	* @package Maple CMS
	* @author Rubixcode
	*/
	class TEMPLATE extends TwigRenderEngine{
		/**
		 * Template Configuration
		 * @var array {
		 *      @type boolean 'render' if render engine should be used
		 *      @type boolean 'show-template' if the template content should also be returned
		 * }
		 */
		private static $_configuration = [
			"render"	=>	false,
			"show-template"	=>	false,
		];

		/**
		 * Render Defaults
		 * @var array {
		 *      @type array 'maple' {
		 *            @type array 'permission',
		 *            @type array 'request',
		 *            @type array 'url',
		 *            @type array 'site',
		 *      },
		 *      @type array 'theme'
		 * }
		 */
		private static $__render_defaults = [];

		/**
		 * Set Template render configuration
		 */
		private static function set_configuration(){
			if(isset($_REQUEST["maple-template"]) && is_array($_REQUEST["maple-template"])){
				if(isset($_REQUEST["maple-template"]["render"]) && is_bool($_REQUEST["maple-template"]["render"])) self::$_configuration["render"] = $_REQUEST["maple-template"]["render"];
				if(isset($_REQUEST["maple-template"]["show-template"]) && is_bool($_REQUEST["maple-template"]["show-template"])) self::$_configuration["show-template"] = $_REQUEST["maple-template"]["show-template"];
			} else {
				self::$_configuration["render"] = true;
			}
		}

		/**
		 * set render defaults
		 * @uses SECURITY::get_permissions
		 * @uses SITE::name
		 * @uses SITE::owner
		 * @uses URL::http
		 * @uses THEME::rendering_data
		 */
		private static function set_render_defaults(){
			return [
				"maple"	=>	[
					"permission"=>	SECURITY::get_permissions(),
					"request"	=>	$_REQUEST,
					"site"		=>	[
						"name"		=>	SITE::name(),
						"owner"		=>	[
							"name"		=>	SITE::owner("name"),
							"link"		=>	SITE::owner("link"),
						],
					],
					"url"	=>	[
						'root'		=>	URL::http("%ROOT%"),
						'admin'		=>	URL::http("%ADMIN%"),
						'plugin'	=>	URL::http("%PLUGIN%"),
						'include'	=>	URL::http("%INCLUDE%"),
						'vendor'	=>	URL::http("%VENDOR%"),
						'current'	=>	URL::http("%CURRENT%"),
						'data'		=>	URL::http("%DATA%"),
						'theme'		=>	URL::http("%THEME%"),
					],
				],
				"theme"	=>	THEME::rendering_data(),
			];
		}

		/**
		 * initialize template engines
		 * @api
		 * @uses THEME::initialize
		 * @throws \RuntimeException if \maple\cms\URL is not initialized
		 * @throws \RuntimeException if \maple\cms\SECURITY is not initialized
		 * @throws \RuntimeException if \maple\cms\SITE is not initialized
		 */
		public static function initialize(){
			if(!URL::initialized()) throw new \RuntimeException("'\\maple\\cms\\URL' must be initialized", 1);
			if(!SECURITY::initialized()) throw new \RuntimeException("'\\maple\\cms\\SECURITY' must be initialized", 1);
			if(!SITE::initialized()) throw new \RuntimeException("'\\maple\\cms\\SITE' must be initialized", 1);

			THEME::initialize();
			self::set_configuration();
			self::$__render_defaults = self::set_render_defaults();
			parent::$_extention = "html";

			if(self::$_configuration["render"]){
				parent::$_render_defaults = self::$__render_defaults;
				parent::initialize();
			}
		}

		/**
		 * Render Template
		 * @api
		 * @throws \InvalidArgumentException if Argument #1, #2 or #3 are not of type 'string','string' or 'array' respectively
		 * @throws \maple\cms\exceptions\RenderEngineException if Twig not initialized properly
		 * @param  string $namespace template namespace
		 * @param  string $template  template name
		 * @param  array  $data      data
		 * @return string            content
		 */
		public static function render($namespace,$template,$data = []){
			if(self::$_configuration["render"]) return parent::render($namespace,$template,$data);
			else return [
				"details"	=>	[
					"type"		=>	"template",
					"namespace"	=>	$namespace,
					"template"	=>	$template,
				],
				"template"	=> (
					self::$_configuration["show-template"]?
					parent::get_template($namespace,$template):
					""
				),
				"data"	=> $data
			];
		}

		/**
		 * Render Text
		 * @api
		 * @throws \InvalidArgumentException if Argument #1 or #2 are not of type 'string' or 'array' respectively
		 * @param  string $text		 template string
		 * @param  array  $data      data
		 * @return string            content
		 */
		public static function render_text($text,$data = []){
			if(self::$_configuration["render"]) return parent::render_text($text,$data);
			else return [
				"details"	=>	[
					"type"	=>	"text",
				],
				"template"	=> (
					self::$_configuration["show-template"]?
					$text: ""
				),
				"data"	=> $data
			];
		}

		/**
		* Render Text
		* @api
		* @uses self::render_text to render
		* @throws \InvalidArgumentException if Argument #1 or #2 are not of type 'string' or 'array' respectively
		* @throws \maple\cms\exceptions\FileNotFoundException if $file is missing
		* @param  string $file		file path
		* @param  array  $data      data
		* @return string            content
		*/
		public static function render_file($file, $data = []){
			if(self::$_configuration["render"]) return parent::render_text($file,$data);
			else return [
				"details"	=>	[
					"type"	=>	"file",
					"file"	=>	$file,
				],
				"template"	=> (
					self::$_configuration["show-template"]?
					file_get_contents($file): ""
				),
				"data"	=> $data
			];
		}

		/**
		 * Add Template sources
		 * @api
		 * @throws \InvalidArgumentException if Argument #1 is not of type 'array'
		 * @throws \DomainException if "namespace" or "source" is not of type 'array'
		 * @param array $namespaces {
		 *        must be formated as
		 *        "namespace" => "source"
		 *        where "source" is file path
		 * }
		 */
		public static function add_template_sources($namespaces){ parent::add_template_sources($namespaces); }

		/**
		 * Add Default sources for template engines
		 * NOTE : pre initialized template do not have any effect
		 * @api
		 * @throws \InvalidArgumentException if Argument #1 is not of type 'string'
		 * @param file-path $source source
		 */
		public static function add_default_template_source($source){
			parent::add_default_template_source($source);
		}

	};
?>
