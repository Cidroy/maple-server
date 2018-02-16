<?php
namespace maple\cms;

/**
 * Ajax Handler class
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class AJAX {
	/**
	 * Cache File Location
	 * @var string file-path
	 */
	const cache = \ROOT.\CACHE."/ajax";

	/**
	 * Cache File Location
	 * @var string file-path
	 */
	const config = \ROOT.\CONFIG."/ajax";

	/**
	 * Cache File Location
	 * @var string file-path
	 */
	const config_file = "/configuration.json";

	/**
	 * Error listing
	 * @var array
	 */
	const error = [
		"invalid-input"	=>	[400,"Invalid Ajax Input Given"],
		"invalid-api"	=>	[400,"Invalid Ajax action"],
		"insufficient-permission"	=>	[400,"Invalid Ajax action"],
		"nonce-expired"	=>	[400,"Action Timed Out"],
		"invalid-api-file"	=>	[500,"Api file could not be parsed"],
	];

	/**
	 * Required Request Parameters to be considered as ajax
	 * @var array
	 */
	const request_parameters = [
		"namespace" => "maple-ajax",
		"action"	=> "maple-ajax-action"
	];

	/**
	 * default CORS config and other headers
	 * @var array
	 */
	const default_configuration = [
		"Access-Control"	=>	[
			"Allow-Origin"	=>	"*",
			"Allow-Credentials" =>	true,
			"Allow-Methods"	=>	["GET","POST","HEAD"],
			"Allow-Headers"	=>	"Content-Type, *"
		],
		"base"	=>	"%ROOT%/ajax"
	];

	/**
	 * Default Configuration for Ajax
	 * @var array
	 */
	const ajax_default = [
		"mime"	=>	"application/json",
		"method"=>	["GET","POST","PUT","DELETE"],
		"extention" =>	"json",
		"cache"	=>	"default",
		"permission"	=>	"*",
		"arguments"	=>	[],
	];

	/**
	 * List of ajax namespace and their file path
	 * @var array
	 */
	private static $apis = [];

	/**
	 * Default headers to be used
	 * @var array
	 */
	private static $default_headers = [];

	/**
	 * Return if ajax requested
	 * @throws \InvalidArgumentException if $method is not of type 'string'
	 * @param string $method http request method. Default - "*"
	 * @return boolean status
	 */
	public static function is_ajax($method = "request"){
		static $buffer = null;
		if($buffer===null) $buffer = array_flip(self::request_parameters);
		$method = strtolower($method);
		switch ($method) {
			case 'get': $method = $_GET; break;
			case 'post': $method = $_GET; break;
			case 'request':
			case '*':
			default: $method = $_REQUEST; break;
		}
		return !array_diff_key($buffer,$method) && URL::http("%API%") === URL::http("%CURRENT%");
	}

	/**
	 * Ajax System Initialiation
	 * @uses \maple\cms\URL::add() to register base api
	 * @uses \maple\cms\MAPLE::_get() to get all apis registration
	 */
	public static function initialize(){
		if(!file_exists(self::cache) || !file_exists(self::config.self::config_file)) self::diagnose();
		self::$apis = MAPLE::_get("apis");
		$configuration = json_decode(file_get_contents(self::config.self::config_file),true);
		URL::add("%API%",$configuration["base"],null);
		if($configuration["Access-Control"]){
			if(isset($_SERVER["HTTP_ORIGIN"]) && $configuration["Access-Control"]["Allow-Origin"]=="*") $configuration["Access-Control"]["Allow-Origin"] = $_SERVER["HTTP_ORIGIN"];
			foreach ($configuration["Access-Control"] as $key => $value) self::$default_headers["Access-Control-{$key}"] = $value;
		}
	}

	/**
	 * run diagnostics
	 * checks if cache,config folder exists
	 */
	private static function diagnose(){
		if(!file_exists(self::cache)) mkdir(self::cache,0777,true);
		if(!file_exists(self::config)) mkdir(self::config,0777,true);
		if(!file_exists(self::config.self::config_file)) file_put_contents(self::config.self::config_file,json_encode(self::default_configuration));
	}

	/**
	 * Automatically Handle Ajax Request
	 * @api
	 */
	public static function handle(){

		if(!self::is_ajax()) return;
		$headers = self::$default_headers;
		$ajax = [];
		$output = "";
		try {
			self::initialize();
			$namespace = $_REQUEST[self::request_parameters["namespace"]];
			$action = $_REQUEST[self::request_parameters["action"]];

			if(!($ajax = self::get($namespace,$action))) throw new \Exception("invalid-api-file",1);
			if(!($ajax = self::allowed($ajax))) throw new \Exception("insufficient-permission",2);
			$ajax  = current($ajax);
			if(((isset($ajax["nonce"]) and $ajax["nonce"]) or SECURITY::is_nonce()) and !SECURITY::verify_nonce()) throw new \Exception("nonce-expired",3);
		} catch (\Exception $e) {
			$args = [
				"type"		=>	"error",
				"message"	=> self::error[$e->getMessage()]!==null ? self::error[$e->getMessage()][1] : "Something Went Wrong",
				"code"		=>	$e->getCode(),
				"response-code" => self::error[$e->getMessage()]!==null ? self::error[$e->getMessage()][0]: 500,
				"error"		=>	\DEBUG?$e->getTrace():false,
			];
			$ajax = [
				"cache"		=>	"default",
				"mime"		=>	"default",
				"extention"	=>	"json",
				"function"	=>	"json_encode",
				"arguments"	=>	$args
			];
			http_response_code($args["response-code"]);
		}
		try {
			echo self::serve($ajax,$headers);
			\ENVIRONMENT::headers($headers);
		} catch (\Exception $e) {
			$args = [
				"type" 		=> "error",
				"message" 	=> "Something Went Wrong",
				"code" 		=> $e->getCode(),
				"response-code" => 500,
				"error" 	=> \DEBUG ? $e->getTrace() : false,
			];
			http_response_code(500);
			echo(json_encode($args,JSON_PRETTY_PRINT));
		}
		die();
	}

	/**
	 * Get Ajax Details
	 * @api
	 * @throws \InvalidArgumentException if argument #1 and #2 are not of type 'string'
	 * @throws \Exception if the file for ajax namespace could not be parsed
	 * @param  string $namespace ajax namespace
	 * @param  string $action    ajax action
	 * @return array            ajax
	 */
	public static function get($namespace,$action){
		if(!isset(self::$apis[$namespace])) return false;
		$ajax = json_decode(file_get_contents(self::$apis[$namespace]),true);
		if(!is_array($ajax)) throw new \Exception("invalid-api-file");
		if(!array_key_exists($action,$ajax)){
			if(array_key_exists("*",$ajax)) return [$ajax["*"]];
			return false;
		}
		if(!isset($ajax[$action][0])) $ajax = [$ajax[$action]];
		return $ajax;
	}

	/**
	 * Get allowed ajax from ajax list configuration
	 * @api
	 * @param  array  $ajax ajax list
	 * @param  array  $configuration
	 * [optional] if any different configuration is needed
	 * [default] Current Environment Configuration
	 * @return array       allowed ajax list
	 */
	public static function allowed($ajax,$configuration = []){
		static $default_config = false;
		if($default_config===false) $default_config = [
			"method"	=>	isset($_SERVER["REQUEST_METHOD"])?$_SERVER["REQUEST_METHOD"]:"GET",
		];
		$configuration = array_merge($configuration,$default_config);
		$do_ajax = [];
		foreach ($ajax as $a){
			$a["permission"] = isset($a["permission"])?$a["permission"]:[];
			$a = array_merge(self::ajax_default,$a);
			if(!SECURITY::permission(null,$a["permission"])) continue;
			if(is_string($a["method"])) $a["method"] = [$a["method"]];
			if(!in_array($configuration["method"],$a["method"])) continue;
			$do_ajax[] = $a;
		}
		return $do_ajax;
	}

	/**
	 * Serve ajax configuration
	 * @api
	 * @param  array $ajax ajax details
	 * @param  array $headers headers
	 * this will return the neccessary headers that ajax asked
	 * @return mixed       output
	 */
	public static function serve($ajax,&$headers = []){
		if(!$ajax) return;
		if($ajax["extention"]!="default") $headers["Content-Type"] = FILE::mime("*.{$ajax["extention"]}");
		if($ajax["mime"]!="default") $headers["Content-Type"] = $ajax["mime"];
		if($ajax["cache"]!="default") $headers["Cache-Control"] = $ajax["cache"];
		if($ajax["extention"]=="default" && $ajax["mime"]=="default") $headers["Content-Type"] = FILE::mime("*.json");
		ob_start();
		$output = call_user_func($ajax["function"],$ajax["arguments"]);
		if(!is_string($output)) $output = json_encode($output,JSON_PRETTY_PRINT);
		ob_end_clean();
		return $output;
	}
}

?>
