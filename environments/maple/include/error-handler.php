<?php
/**
* Handles Maple Framework Error
* Handles File Missing and other tasks
* @package Maple Framework
*/
class ERROR
{
	private static $_type	=	array();
	private static $_data	=	array();
	private static $_message = array();
	public static $Critical	=	false;	// Terminates the Framework if true
	public static $Allow_Termination = false;	// Allows the system to crash on critical system error
	private static $_whoops_reporting_active = false;
	private static $_whoops_reporting_loaded = false;
	private static $_debugbar_active = false;
	private static $_debugbar_loaded = false;
	private static $_debugbar = null;
	private static $_debugbar_render = null;
	private static $_config = false;

	/**
	 * Returns the error for the code stored
	 * @return string
	 */
	private static function __getTitle(){
		$str = '';
		//	#VICKY [+] : add PyCode for getting title
		return $str;
	}

	/**
	 * Get weather the error(s) that have occured is(consist a) critical failure
	 * If true then the system must shut down.
	 * @return bool
	 */
	private static function __getCritical(){
		$bool = false;
		// #VICKY [+] : add PyCode for getting
		return $bool;
	}

	/**
	 * Constructor for the error handler if an object is used
	 * Probably not
	 */
	function __construct(){  }

	/**
	 * The type of error that has currently/recetly occured
	 * types : Critical, Alert, Warning, Info
	 * @return self::eType
	 */
	public static function Type($str){	$_type	=	$str;	}
	public static function Data($str){	$_data	=	$str;	}
	public static function Message($str){	$_message	=	$str;	}

	/**
	 * Returns the JSON format of the error
	 * @return JSON
	 */
	public static function JSON(){
		$error = array();
		$error['Code']		= $_type;
		$error['Title'] 	= __getTitle();
		$error['Data']		= $_data;
		$error['Message']	= $_message;
		$error['Critical']	= __getCritical();
		return json_encode($error) ;
	}
	/**
	 * return the HTML format of error
	 * @return HTML
	 */
	public static function HTML(){
		$error = array();
		$error['Code']		= $_type;
		$error['Title'] 	= __getTitle();
		$error['Data']		= $_data;
		$error['Message']	= $_message;

		$html = "";
		// #VICKY [+] : create HTML return style here.
		return $html;
	}
	/**
	 * Return the polymer type error
	 * @return HTML
	 */
	public static function Polymer(){
		$polymer='';
		// #VICKY [+] : return polymer style error
		return $polymer;
	}

	/**
	 * Return the current error suitably formatted for return to the user
	 * Default value as initialized in @var Response_type
	 * @param type : [JSON , HTML , Polymer]
	 * @return string : formatted
	 */
	public static function Error($type=''){
		if($type=='') $type=MAPLE::$Response_Type;
		switch ($type) {
			case 'JSON': return JSON(); break;
			case 'HTML': return HTML(); break;
			case 'Polymer': return Polymer(); break;
		}
	}

	public static function Exception($msg,$num){
		// TODO
	}

	/**
	 * Uses Config to decide initialization of error reporting
	 * Later can be overridden.
	 */
	public static function Initialize(){
		if(!self::$_config){
			$config = json_decode(FILE::read(ROOT.INC."config/error-reporting.json"),true);
			self::$_config = $config;

			if($config["prettify-error"]){
				self::LoadWhoopsErrorHandler();
				self::StartWhoopsErrorHandler();
			}

			if($config["debug"]["active"] && $config["debug"]["show-debug-bar"]){
				self::LoadDebugBar();
			} else require_once __DIR__."/class-log.php";
		}
	}

	public static function LoadWhoopsErrorHandler(){
		if(!self::$_whoops_reporting_loaded){
			require_once(ROOT.INC."/Vendor/Whoops/autoload.php");
			self::$_whoops_reporting_loaded = true;
		}
	}
	public static function StartWhoopsErrorHandler(){
		if(!self::$_whoops_reporting_active){
			$run     = new \Whoops\Run;
			$handler = new \Whoops\Handler\PrettyPageHandler;
			$JsonHandler = new \Whoops\Handler\JsonResponseHandler;

			$run->pushHandler($JsonHandler);
			$run->pushHandler($handler);
			$run->register();
			self::$_whoops_reporting_active = true;
		}
	}

	public static function LoadDebugBar(){
		if(!self::$_debugbar_loaded){
			require_once(ROOT.INC."/Vendor/DebugBar/autoload.php");
			self::$_debugbar_loaded = true;

			self::$_debugbar = new DebugBar\StandardDebugBar();
			self::$_debugbar_render = self::$_debugbar->getJavascriptRenderer();
			$base = URL::http("%INCLUDE%");
			$base = "{$base}/Vendor/DebugBar/maximebf/debugbar/src/DebugBar/Resources/";
			self::$_debugbar_render->setBaseUrl($base);
			require_once __DIR__."/class-log-bar.php";
		}
	}

	public static function ShowDebugBar(){
		if(self::$_debugbar){
			return self::$_debugbar_render->renderHead().self::$_debugbar_render->render();
		}
		else{
			return false;
		}
	}

	public static function Log($value='',$method){
		if(!self::$_debugbar){
			return false;
		};
		switch ($method) {
			case 'emergency'	: self::$_debugbar["messages"]->emergency($value); 	break;
			case 'alert'		: self::$_debugbar["messages"]->alert($value); 		break;
			case 'critical'		: self::$_debugbar["messages"]->critical($value); 		break;
			case 'error'		: self::$_debugbar["messages"]->error($value); 		break;
			case 'warning'		: self::$_debugbar["messages"]->warning($value); 		break;
			case 'notice'		: self::$_debugbar["messages"]->notice($value); 		break;
			case 'info'			: self::$_debugbar["messages"]->info($value); 			break;
			case 'debug'		: self::$_debugbar["messages"]->debug($value); 		break;
			case 'addMessage'	: self::$_debugbar["messages"]->addMessage($value); 	break;
		}
	}

	public static function StartTimer($name,$description){
		if(!self::$_debugbar){
			return false;
		};
		self::$_debugbar["time"]->startMeasure($name,$description);
	}
	public static function StopTimer($name){
		if(!self::$_debugbar){
			return false;
		};
		self::$_debugbar["time"]->stopMeasure($name);
	}
	public static function Timer($description,$param){
		if(!self::$_debugbar){
			return false;
		};
		self::$_debugbar["time"]->Measure($description,$param);
	}

};

ERROR::Initialize();
?>
