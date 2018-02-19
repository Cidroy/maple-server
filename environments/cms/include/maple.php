<?php
namespace maple\cms;
use maple\cms\cms_dependency;

/**
* The Main Maple Framework class for the system to work
* Please use this class functions for api project
* @since 1.0
* @package Maple CMS
* @author Rubixcode
*/
class MAPLE{
	/**
	 * List of class name to be autoloaded from files
	 * @var array class-name => root file location
	 */
	private static $_AUTOLOAD_LIST = [];
	/**
	 * Store filters and its binded functions in queue
	 * "filter" => [functions,...]
	 * @var array
	 */
	private static $_FILTERS = [];
	/**
	 * Store shortcodes
	 * shortcode => function
	 * @var array
	 */
	private static $_SHORTCODES = [];
	/**
	 * Store router files location
	 * namespace =>	file
	 * @var array
	 */
	private static $_ROUTERS = [];
	/**
	 * Store templates locations
	 * namespace => file
	 * @var array
	 */
	private static $_TEMPLATES = [];
	/**
	 * Api files locations
	 * namespace => file
	 * @var array
	 */
	private static $_API = [];

	/**
	 * Ui related data
	 * @var array
	 */
	private static $_UI = [
		"dashboard"=>[],
		"menus"=>[],
		"widgets"=>[],
	];

	/**
	 * Hooks List
	 * @var array
	 */
	private static $hooks = [];

	/**
	 * Stores if Maple CMS has Content to display
	 * @var boolean
	 */
	private static $has_content = false;

	/**
	 * Return / Set Content Status
	 * @param  boolean  $status if content available
	 * @return boolean         has content status
	 */
	public static function has_content($status = null){
		if($status!==null) self::$has_content = $status;
		return self::$has_content;
	}

	/**
	 * initialize Maple for use
	 * @api
	 * @throws \DomainException if all the array variables are not provided
	 * @throws \InvalidArgumentException if all parameters are not of type array
	 * @param  array $data plugin details.{
	 *         # cms plugin data
	 *         @type array 'autoload'	: class 	=> file
	 *         @type array 'filters'	: name		=> function
	 *         @type array 'shortcodes'	: shortcode => function
	 *         @type array 'routers'	: router	=> file
	 *         @type array 'templates'	: template	=> folder
	 *         @type array 'api'		: namespace	=> folder
	 *
	 *			# ui data
	 *         @type array 'dashboard'	{
	 *               @type string 'name'
	 *               @type string 'function'
	 *               @type string 'style'
	 *               @type array 'permission' : namespace => permission
	 *         }
	 *         @type array 'menus'		{
	 *               @type string 'name'
	 *               @type array 'route'{
	 *                     @type string 'namespace'
	 *                     @type string 'name'
	 *               }
	 *               @type array 'permission' : namespace => permission
	 *         }
	 *         @type array 'widgets'	{
	 *               @type string 'name'
	 *               @type string 'function'
	 *               @type array 'permission' : namespace => permission
	 *         }
	 * }
	 */
	public static function initialize($data){
		$req = ["autoload","filters","shortcodes","routers","templates","dashboard","menus","widgets"];
		if(array_diff($req,array_keys($data))) throw new \DomainException("Parameters missing, please read documentation", 1);
		foreach ($data as $key => $value) if(!is_array($data[$key])) throw new \InvalidArgumentException("Invalid Parameters, Please read documentation", 1);

		self::$_AUTOLOAD_LIST = array_merge($data["autoload"],self::$_AUTOLOAD_LIST);
		self::$_FILTERS = array_merge($data["filters"],self::$_FILTERS);
		self::$_SHORTCODES = array_merge($data["shortcodes"],self::$_SHORTCODES);
		self::$_ROUTERS = array_merge($data["routers"],self::$_ROUTERS);
		self::$_TEMPLATES = array_merge($data["templates"],self::$_TEMPLATES);
		self::$_API = array_merge($data["api"],self::$_API);

		self::$_UI = [
			"dashboard"	=>	array_merge($data["dashboard"],self::$_UI["dashboard"]),
			"menus"		=>	array_merge($data["menus"],self::$_UI["menus"]),
			"widgets"	=>	array_merge($data["widgets"],self::$_UI["widgets"]),
		];
	}

	/**
	 * Combined Plugin data
	 * @access private
	 * @throws \InvalidArgumentException if $detail not of type 'string'
	 * @param string $detail detail to obtain
	 * @param  boolean $erase should erase
	 * @return array  sources
	 */
	public static function _get($detail,$erase = false){
		if(!is_string($detail)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		switch ($detail) {
			case 'apis': return self::$_API; if($erase) self::$_API = []; break;
			case 'ui': return self::$_UI; if($erase) self::$_UI = []; break;
			case 'routers': return self::$_ROUTERS; if($erase) self::$_ROUTERS = []; break;
			case 'templates': return self::$_TEMPLATES; if($erase) self::$_TEMPLATES = []; break;
			default: return []; break;
		}
	}

	/**
	 * SPL Autoload handler for Maple CMS
	 * @uses self::$_AUTOLOAD_LIST
	 * @uses LOG::error
	 * @api
	 * @param  string $class class to autoload
	 * @return boolean        status
	 */
	public static function autoloader($class){
		if(isset(self::$_AUTOLOAD_LIST[$class]) && file_exists(self::$_AUTOLOAD_LIST[$class])){
				require_once self::$_AUTOLOAD_LIST[$class];
				return true;
		}
		// else Log::error("$class not specified for autoload");
		return false;
	}

	/**
	 * Add class to be dynamically autoload on call
	 * returns false if already class already added to autload
	 * @api
	 * @throws \InvalidArgumentException if Arguments $class,$location are not of type 'string'
	 * @param string $class class name with namespace
	 * @param file-path $location   path to class file
	 * @param boolean $force set to true to replace existing path
	 * @return boolean status
	 */
	public static function add_autoloader($class,$location,$force = false){
		if(!is_string($class)) throw new \InvalidArgumentException("Argument #1 must be of type 'string' but '".gettype($class)."' passed", 1);
		if(!is_string($location)) throw new \InvalidArgumentException("Argument #2 must be of type 'string' but '".gettype($location)."' passed", 1);
		if(!$force && isset(self::$_AUTOLOAD_LIST[$class])){
				Log::warning("Unable to set autloader for {$class} at location : '{$location}' because it is already set to location : '{self::$_AUTOLOAD_LIST[$class]}'");
				return false;
		}
		self::$_AUTOLOAD_LIST[$class] = $location;
		return true;
	}

	/**
	 * This is explicit event binder that can be called
	 * NOTE : for the purpose of simplicity
	 * 		  prefer adding the filter in your package file
	 * 		  rather than calling this functions.
	 * 		  It is only for dynamic initialization
	 * 		  and has high chance that its 'do_filters' was already called before binding,
	 * 		  hence there may be varied results.
	 * TODO : add option to set priority , send call parameter : filter_name
	 * @api
	 * @throws \InvalidArgumentException if $filter is not of type 'string'
	 * @throws \InvalidArgumentException if $function is not of type 'string'
	 * @throws \InvalidArgumentException if $args is not of type 'array'
	 * @param string 	$filter name of event
	 * @param string 	$function   function name that needs to trigger with it
	 * @param mixed[] 	$args Arguments to be passed
	 */
	public static function add_filter($filter,$function,$args = []){
		if(!is_string($filter)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($function)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!is_array($args)) throw new \InvalidArgumentException("Argument #3 must be of type 'array'", 1);

		if(!isset(self::$_FILTERS[$filter]))
			self::$_FILTERS[$filter] = [];
		self::$_FILTERS[$filter] = [
			"function" => $function,
			"args" => $args,
		];
	}


	/**
	 * This is an event trigger function that is called in explicitly by the programmer
	 * once called the functions bound to the event will return all data
	 * NOTE : always echo 'MAPLE::do_filters('name',[])' as it implicitly does not
	 * TODO : provide ordered calling functionality and stack handling for cases of recursion
	 * @param  string $filter 	name of event
	 * @param  array $args  	any specific parameter
	 * @return object          	a collective dump of function returns appended
	 */
	public static function do_filters($filter,$args=[]){
		if(!is_string($filter)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_array($args)) throw new \InvalidArgumentException("Argument #2 must be of type 'array'", 1);
		$errors = [];
		if(isset(self::$_FILTERS[$filter])){
			reset(self::$_FILTERS[$filter]);
			ob_start();
			while ($f = current(self::$_FILTERS[$filter])) {
				try{
					$result = call_user_func($f["function"], array_merge($f["args"], $args));
					if (is_array($result)) $args = array_merge($args, $result);
				} catch(\Exception $e){ $errors[] = $e; }
				next(self::$_FILTERS[$filter]);
			}
			ob_end_clean();
		}
		return new __filter_content($args,$errors);
	}

	/**
	 * Add Content Hooks
	 * @api
	 * @throws \InvalidArgumentException if $function not of type 'string'
	 * @throws \InvalidArgumentException if $args not of type 'array'
	 * @throws \InvalidArgumentException if $priority not of type 'integer'
	 * @param string  $function function name
	 * @param mixed $args     function arguments
	 * @param integer $priority priority
	 */
	public static function hook($function,$args = null,$priority = 0){
		if(!is_string($function)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_integer($priority)) throw new \InvalidArgumentException("Argument #3 must be of type 'string'", 1);

		if(!isset(self::$hooks[$priority])) self::$hooks[$priority] = [];
		self::$hooks[$priority][] = [
			"function"	=>	$function,
			"arguments"	=>	$args
		];
	}

	/**
	 * Return content hooks based on priority
	 * @api
	 * @throws \InvalidArgumentException if $priority not of type 'integer'
	 * @param  integer $priority priority
	 * @return array   hooks
	 */
	public static function hooks($priority = false){
		if($priority===false) return self::$hooks;
		else if(is_integer($priority)) return isset(self::$hooks[$priority])?self::$hooks[$priority]:[];
		else throw new \InvalidArgumentException("Argument #3 must be of type 'string'", 1);
	}

	/**
	 * Do hooks and return content
	 * @return mixed[string,array] content
	 */
	public static function do_hooks(){
		$output = null;
		while(self::$hooks){
			reset(self::$hooks);
			$priority = key(self::$hooks);
			while (self::$hooks[$priority]) {
				reset(self::$hooks[$priority]);
				$hook = key(self::$hooks[$priority]);
				// BUG: does not handle echo's
				// ob_start();
					$ret = call_user_func(self::$hooks[$priority][$hook]["function"],self::$hooks[$priority][$hook]["arguments"]);
					// $out = ob_get_contents();
					if(is_null($output)) $output = $ret;
					else if(is_array($output) && $ret) {
						$ret = is_array($ret)?$ret:[$ret];
						$output = array_merge($output,$ret);
					} else if (is_string($output) && $ret){
						$ret = is_string($ret)?$ret:json_encode($ret);
						$output = $output.$ret;
					}
				// ob_end_clean();
				unset(self::$hooks[$priority][$hook]);
			}
			unset(self::$hooks[$priority]);
		}
		return $output;
	}

	/**
	 * Return the function associated to shortcode
	 * @param  mixed $shortcode shortcode
	 * accepts \maple\cms\SHORTCODE, string.
	 * @return string            functions
	 * returns false if none
	 */
	public static function sc_function($shortcode){
		if($shortcode instanceof SHORTCODE) $shortcode = $shortcode->name;
		if(!is_string($shortcode))	throw new \InvalidArgumentException("Argument #1 must be of type 'string' or '\\maple\\cms\\SHORTCODE'", 1);
		return isset(self::$_SHORTCODES[$shortcode])?self::$_SHORTCODES[$shortcode]:false;
	}

	/**
	 * Return Debug info
	 * NOTE : requires debug switch to be on
	 * @return array debug info
	 */
	public static function debug(){
		if(!\DEBUG) return [];
		return [
			"AUTOLOAD_LIST" => self::$_AUTOLOAD_LIST,
			"FILTERS" 		=> self::$_FILTERS,
			"SHORTCODES" 	=> self::$_SHORTCODES,
			"ROUTERS" 		=> self::$_ROUTERS,
			"TEMPLATES" 	=> self::$_TEMPLATES,
			"UI"		 	=> self::$_UI,
		];
	}

}
/**
 * Output for filter
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class __filter_content{
	public $content = [];
	public $errors = [];
	public function __construct($data,$errors = []){ 
		$this->content = $data;
		$this->errors  = $errors; 
	}
	public function __toString(){
		$string = "";
		foreach ($this->content as $value) {
			if(is_string($value)) $string = $string.$value;
			if(is_array($value)) $string = $string.@implode("",$value);
		}
		return $string;
	}
}

?>
