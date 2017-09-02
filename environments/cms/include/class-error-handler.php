<?php
namespace maple\cms;
use \ROOT;
use \VENDOR;
use \CONFIG;

/**
* Handles Maple CMS Error
* Handles File Missing and other tasks
* @package Maple CMS
* @since 1.0
* @author Rubixcode
*/
class ERROR
{
	/**
	 * location to error reporting configuration folder
	 * @var file-path
	 */
	const _conf_file = ROOT.CONFIG."/error-reporting.json";
	/**
	 * location to log class alternatives
	 * @var array {
	 *      @type file-path 'basic'
	 *      @type file-path 'debugbar'
	 * }
	 */
	const _class_logger = [
		"basic"	=>	ROOT.INC."/class-log-basic.php",
		"debugbar"	=>	ROOT.INC."/class-log-debugbar.php",
	];
	/**
	 * Default configuration for error reporting
	 * @var array {
	 *      @type boolean 'prettify-error'
	 *      @type array 'debug' {
	 *            @type boolean 'active'
	 *            @type boolean 'show-debug-bar'
	 *      }
	 * }
	 */
	const _default_configuration = [
		"prettify-error"	=>	false,
		"debug"				=>	[
			"active"	=>	false,
			"show-debug-bar"	=>	false,
		]
	];
	/**
	 * Details Related to whoops error handler
	 * @var array
	 */
	private static $_whoops = [
		"autloader"	=>	ROOT.VENDOR."/Whoops/autoload.php",
		"active"	=>	false,
		"loaded"	=>	null,
	];
	/**
	 * Details Related to debugbar
	 * @var array
	 */
	private static $_debugbar = [
		"location"	=>	ROOT.VENDOR."/DebugBar",
		"autoloader"	=>	ROOT.VENDOR."/DebugBar/autoload.php",
		"active"	=>	false,
		"loaded"	=>	null,
		"render"	=>	false,
		"object"	=>	false,
	];
	/**
	 * configuration
	 * @var boolean
	 */
	private static $_configuration = false;

	/**
	 * Initialize error handler
	 * @uses __CLASS__::diagnose()
	 */
	public static function initialize(){
		try {
			if(!file_exists(self::_conf_file)) self::diagnose();
			if(!self::$_configuration){
				self::$_configuration = json_decode(file_get_contents(self::_conf_file),true);
				if(self::$_configuration["prettify-error"]){
					self::load_error_handler();
					self::start_error_handling();
				}
				if(\DEBUG && self::$_configuration["debug"]["active"] && self::$_configuration["debug"]["show-debug-bar"]) {
					self::load_debug_bar();
				}
				else require_once self::_class_logger["basic"];
			}
		} catch (\Exception $e) {
			self::$_configuration = self::_default_configuration;
			self::log($e->getMessage(),"emergency");
			if(!class_exists("\\maple\\cms\\Log")) require_once self::_class_logger["basic"];
		}
	}

	/**
	 * run error handler diagnostics
	 * @throws \maple\cms\exceptions\FilePermissionException if self::_conf_file is not a writable file
	 */
	private static function diagnose(){
		if(!file_exists(self::_conf_file)){
			file_put_contents(self::_conf_file,json_encode(self::_default_configuration));
		}
	}

	/**
	 * Load Error reporting
	 * @api
	 * @uses Whoops Error Handler
	 * @throws \maple\cms\VendorMissingException if Whoops Vendor is Missing
	 * @return boolean status
	 * returns false if error reporting is not allowed
	 */
	public static function load_error_handler(){
		try {
			if(self::$_configuration["prettify-error"]){
				if(self::$_whoops["loaded"] === null){
					if(!file_exists(self::$_whoops["autloader"]))
						throw new \maple\cms\exceptions\VendorMissingException("Unable to locate vendor 'Whoops', please Install now", 1);
					require_once(self::$_whoops["autloader"]);
					self::$_whoops["loaded"] = true;
				}
			} else self::$_whoops["loaded"] = false;
		} catch (\Exception $e) {
			self::$_whoops["loaded"] = false;
			self::log($e->getMessage(),"emergency");
		}
		return self::$_whoops["loaded"];
	}

	/**
	 * Start error reporting
	 * @api
	 * @uses Whoops Error handler
	 * @return boolean status
	 */
	public static function start_error_handling(){
		try {
			if(self::$_configuration["prettify-error"] && self::$_whoops["loaded"]){
				$run     = new \Whoops\Run;
				$handler = new \Whoops\Handler\PrettyPageHandler;
				$JsonHandler = new \Whoops\Handler\JsonResponseHandler;

				$run->pushHandler($JsonHandler);
				$run->pushHandler($handler);
				$run->register();
				self::$_whoops["active"] = true;
			}
		} catch (\Exception $e) {
			self::log($e->getMessage(),"error");
		}
		return self::$_whoops["active"];
	}

	/**
	 * Loads Debug Bar
	 * @api
	 * @uses maximebf/debugbar
	 * @uses URL::initialized to check if class URL is ready
	 * @uses URL::http to get url to %INCLUDE%
	 * @throws \RuntimeException if URL::initialized returns false
	 * @return boolean status
	 */
	public static function load_debug_bar(){
		try {
			if(self::$_debugbar["loaded"] === null){
				if(!file_exists(self::$_debugbar["autoloader"]))
					throw new \maple\cms\exceptions\VendorMissingException("Unable to load Vendor 'DebugBar', please install now", 1);
				require_once self::$_debugbar["autoloader"];
				self::$_debugbar["object"] = new \DebugBar\StandardDebugBar();
				self::$_debugbar["render"] = self::$_debugbar["object"]->getJavascriptRenderer();
				if(!URL::initialized()) throw new \RuntimeException("\maple\cms\URL was not initialized before", 1);

				$base = URL::http("%ROOT%%VENDOR%")."/DebugBar/maximebf/debugbar/src/DebugBar/Resources/";
				self::$_debugbar["render"]->setBaseUrl($base);
				self::$_debugbar["loaded"] = true;
				require_once self::_class_logger["debugbar"];
			}
		} catch (\Exception $e) {
			self::$_debugbar["loaded"]	= false;
			self::log($e->getMessage(),"error");
		}
		return self::$_debugbar["loaded"];
	}

	/**
	 * return debugbar HTML Content
	 * @return string html content
	 */
	public static function show_debug_bar(){
		if(self::$_debugbar["object"]) return self::$_debugbar["render"]->renderHead().self::$_debugbar["render"]->render();
		else return false;
	}

	/**
	 * Logs the messages sent
	 * @api
	 * @throws \InvalidArgumentException if $message or $level is not of type 'string'
	 * @throws \DomainException if $level is not passed with proper value
	 * @param  string $message  message
	 * @param  string $level alert level
	 * Optional.
	 * Default : 'info'.
	 * Valid Values :
	 * - emergency
	 * - alert
	 * - critical
	 * - error
	 * - warning
	 * - notice
	 * - info
	 * - debug
	 * - addMessage
	 */
	public static function log($message,$level = 'info'){
		// if(!is_string($message) && !in_array($level,["debug"])) throw new \InvalidArgumentException("Argument #1 Must be of type string", 1);
		if(!is_string($level)) throw new \InvalidArgumentException("Argument #2 Must be of type string", 1);

		if(!self::$_debugbar["object"]){
			// TODO: !important! add alternative debug logger
			return false;
		};
		switch ($level) {
			case 'emergency'	: self::$_debugbar["object"]["messages"]->emergency($message); 	break;
			case 'alert'		: self::$_debugbar["object"]["messages"]->alert($message); 		break;
			case 'critical'		: self::$_debugbar["object"]["messages"]->critical($message); 	break;
			case 'error'		: self::$_debugbar["object"]["messages"]->error($message); 		break;
			case 'warning'		: self::$_debugbar["object"]["messages"]->warning($message); 	break;
			case 'notice'		: self::$_debugbar["object"]["messages"]->notice($message); 	break;
			case 'info'			: self::$_debugbar["object"]["messages"]->info($message); 		break;
			case 'debug'		: self::$_debugbar["object"]["messages"]->debug($message); 		break;
			case 'addMessage'	: self::$_debugbar["object"]["messages"]->addMessage($message); break;
			default : throw new \DomainException("Invalid Value for Argument #2", 1);
		}
	}

	/**
	 * Start a timer
	 * @api
	 * @throws \InvalidArgumentException if $name or $description are not of type 'string'
	 * @param  string $name        Timer Name
	 * @param  string $description Timer Description.
	 * Optional.
	 * @return boolean              status
	 */
	public static function start_timer($name,$description = null){
		if(!is_string($name)) throw new \InvalidArgumentException("Argument #1 should be of type string.", 1);
		if(!is_string($description)) throw new \InvalidArgumentException("Argument #2 should be of type string.", 1);

		if(!self::$_debugbar["object"]) return false;
		self::$_debugbar["object"]["time"]->startMeasure($name,$description);
		return true;
	}
	/**
	 * Stop a Timer
	 * @api
	 * @throws \InvalidArgumentException if $name is not of type 'string'
	 * @param  string $name Timer Name
	 * @return boolean       status
	 */
	public static function stop_timer($name){
		if(!is_string($name)) throw new \InvalidArgumentException("Argument #1 should be of type string.", 1);
		if(!self::$_debugbar["object"]) return false;
		self::$_debugbar["object"]["time"]->stopMeasure($name);
		return true;
	}

	/**
	 * Timer
	 * @param  string $description Description
	 * @param  callable $param       function
	 * @return boolean              status
	 */
	public static function timer($description,$param){
		if(!self::$_debugbar["object"]) return false;
		self::$_debugbar["object"]["time"]->Measure($description,$param);
		return true;
	}

};
?>
