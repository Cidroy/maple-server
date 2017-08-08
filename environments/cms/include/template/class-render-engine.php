<?php
namespace maple\cms;
use \ROOT; use \VENDOR; use \DEBUG;

/**
 * Twig Based Render Engine Support
 * TODO : !important! add support to following using TwigSimpleFunction
 * - maple.do_filters(string,array) => MAPLE::do_filters(string,array)
 * @uses symphony/twig
 * @subpackage Twig
 * @version 1.0
 * @package Maple CMS
 * @author Rubixcode
 * @since 1.0
 */
class TwigRenderEngine implements \maple\cms\interfaces\iRenderEngine{
	const _vendor_location = ROOT.VENDOR."/Twig";
	const _vendor_autloader = ROOT.VENDOR."/Twig/autoload.php";
	const _cache_location = ROOT.CACHE."/template";
	// BUG: must be allocated in ENVIRONMENT
	const _template_default = ROOT.__MAPLE__."/templates";

	/**
	 * Twig Render Engine Information
	 * @var array {
	 *      @type \Twig_Loader_Filesystem 'loader' default render source using self::_template_default
	 *      @type \Twig_Environment 'environment' default render engine with loader self::$_twig["loader"]
	 *      @type array "environments" {
	 *            contains other namespaced environments
	 *            "namespace" => [
	 *            		@type file-path "source" template directory
	 *            	    @type \Twig_Loader_Filesystem "loader" uses 'source' and self::_template_default
	 *            	    @type \Twig_Environment "object" twig environment using "loader"
	 *            ]
	 *      }
	 * }
	 */
	private static $_twig = [
		"loader"	=> null,
		"environment"	=> null,
		"environments"	=> [],
	];
	/**
	 * List of default template sources
	 * @var array
	 */
	private static $_default_sources = [];

	/**
	 * Default settings that needs to be passed to render engine
	 * @var array
	 */
	protected static $_render_defaults = [];
	/**
	 * file extention to use
	 * @var boolean
	 */
	protected static $_extention = false;

	/**
	 * Initialize default Twig Render Engine
	 * @api
	 * @throws \maple\cms\exceptions\VendorMissingException if Vendor 'Twig' is missing
	 * @return boolean status
	 */
	public static function initialize(){
		try {
			if(!file_exists(self::_vendor_autloader)) throw new \maple\cms\exceptions\VendorMissingException("Unable to load Vendor 'Twig', please install now ", 1);
			if(!file_exists(self::_template_default)) mkdir(self::_template_default,0777,true);
			require_once self::_vendor_autloader;
			\Twig_Autoloader::register();
			self::$_twig["loader"]  = new \Twig_Loader_Filesystem(self::_template_default);
			self::$_twig["environment"] = new \Twig_Environment(self::$_twig["loader"], [
				'cache' => self::_cache_location,
				'debug' => DEBUG,
			]);
			if(DEBUG)	self::$_twig["environment"]->addExtension(new \Twig_Extension_Debug());
			return true;
		} catch (\Exception $e) {
			LOG::emergency($e->getMessage());
			throw $e;
		}
		return false;
	}

	/**
	 * Render Template
	 * @api
	 * @throws \InvalidArgumentException if Argument #1, #2 or #3 are not of type 'string','string' or 'array' respectively
	 * @param  string $namespace template namespace
	 * @param  string $template  template name
	 * @param  array  $data      data
	 * @return string            content
	 */
	public static function render($namespace, $template, $data = []){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($template)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!is_array($data)) throw new \InvalidArgumentException("Argument #3 must be of type 'array'", 1);

		$_data = is_array(self::$_render_defaults)?self::$_render_defaults:[];
		$data = array_merge($_data,$data);
		if( isset($_twig["environments"][$namespace]) ){
			if(!isset(self::$_twig["environments"][$namespace]["loader"])){
				self::$_twig["environments"][$namespace]["loader"] = new \Twig_Loader_Filesystem(array_merge([
					[self::$_twig["environments"][$namespace]["source"]],
					[self::_template_default],
					self::$_default_sources
				]));
				self::$_twig["environments"][$namespace]["object"] = new \Twig_Environment(self::$_twig["environments"][$namespace]["loader"],[
					"debug"	=>	DEBUG,
					"cache"	=>	self::_cache_location,
				]);
				if(DEBUG) self::$_twig["environments"][$namespace]["object"]->addExtension(new \Twig_Extension_Debug());
			}
			return self::$_twig["environments"][$namespace]["object"]->render($template.".".self::$_extention);
		}
		else if(file_exists(self::_template_default."/{$namespace}/{$template}.".self::$_extention))
			return self::$_twig["environment"]->render("{$namespace}/{$template}.".self::$_extention,$data);
		else return false;
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
		if(!is_string($text)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_array($data)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);

		$data = array_merge(
			(is_array(self::$_render_defaults)?self::$_render_defaults:[]),
			$data
		);
		$template = self::$_twig["environment"]->createTemplate($text);
		return $template->render($data);

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
	public static function render_file($file,$data = []){
		if(!is_string($file)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_array($data)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!file_exists($file)) throw new \maple\cms\exceptions\FileNotFoundException("unablr to locate file '{$file}'",1);
		return self::render_text(file_get_contents($file),$data);
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
	public static function add_template_sources($namespaces){
		if(!is_array($namespaces)) throw new \InvalidArgumentException("Argument #1 must be of type 'array'", 1);
		foreach ($namespaces as $namespace => $source) {
			if(!is_string($namespace)) throw new \DomainException("'namespace' must be of type 'string'", 1);
			if(!is_string($source)) throw new \DomainException("'source' must be of type 'string'", 1);
			self::$_twig["environments"][$namespace]["source"] = $source;
		}
	}

	/**
	 * Add Default sources for template engines
	 * NOTE : pre initialized template do not have any effect
	 * @api
	 * @throws \InvalidArgumentException if Argument #1 is not of type 'string'
	 * @param file-path $source source
	 */
	public static function add_default_template_source($source){
		if(!is_string($source)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if($source) self::$_default_sources[] = $source;
	}

	/**
	 * return template file contents
	 * @api
	 * BUG : Does not return anything
	 * @throws \InvalidArgumentException if Argument #1 is not of type 'string'
	 * @throws \InvalidArgumentException if Argument #2 is not of type 'string'
	 * @param  string $namespace namespace
	 * @param  string $template  template name
	 * @return string            content
	 */
	public static function get_template($namespace,$template){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($template)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		return "";
	}

	/**
	 * Return the base environment object
	 * @api
	 * @return object twig
	 */
	public static function environment(){ return self::$_twig["environment"]; }

}
?>
