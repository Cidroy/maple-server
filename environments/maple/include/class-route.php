<?php
/**
 *	This is Routing class based on FasRoute by nikic
 */
require_once ROOT.INC."/Vendor/FastRoute/autoload.php";

class ROUTE extends URL{
	private static $base_uri = "";
	private static $_post = [];
	private static $_get  = [];
	private static $_mix  = [];
	private static $_cache= ROOT.DATA."/cache/route.cache";
	private static $_status= [
		"EXECUTED" 	=> false,
		"SUCCESS" 	=> false,
		"ERROR" 	=> false,
	];

	// REMOVE :
	// public static function debug(){
	// 	var_dump(self::$_get);
	// 	var_dump(self::$_post);
	// 	var_dump(self::$_mix);
	// }

	/**
	 * Set the base uri for the system
	 * @param path $base
	 */
	public static function SetBaseUri($base) {
		self::$base_uri = $base;
	}

	/**
	 * Returns the base uri
	 */
	public static function BaseUri(){
		return self::$base_uri;
	}

	public static function Status($stat){
		return isset(self::$_status[$stat]) ? self::$_status[$stat] : false;
	}

	/**
	 * Method to add multiple route
	 * @param array|string $method  in GET, POST, PUT, PATCH, DELETE, OPTIONS
	 * @param url $route
	 * @param function $handler
	 * @param function $middleware
	 */
	public static function AddRoute($method,$route,$handler,$middleware=false){
		if(is_array($method)){
			array_push(self::$_mix,[
				"method"	=>	$method,
				"route"		=>	$route,
				"handler"	=>	$handler
			]);
		} else {
			switch ($method) {
				case 'GET':
						array_push(self::$_get,[
							"route"		=>	$route,
							"handler"	=> $handler
						]);
					break;
				case 'POST':
						array_push(self::$_post,[
							"route"		=>	$route,
							"handler"	=> $handler
						]);
					break;
			}
		}
	}

	/**
	 * Add Route for Get Method
	 * @param url  $route
	 * @param function  $handler
	 * @param function $middleware
	 */
	public static function Get($route,$handler,$middleware=false){
		array_push(self::$_get,[
			"route"		=>	$route,
			"handler"	=> $handler
		]);
	}

	/**
	 * Add Route for Post Method
	 * @param url  $route
	 * @param function  $handler
	 * @param function $middleware
	 */
	public static function Post($route,$handler,$middleware=false){
		array_push(self::$_get,[
			"route"		=>	$route,
			"handler"	=> $handler
		]);
	}

	/**
	 * Load Route Details from file
	 * @param JSON $file path to route file
	 */
	public static function UseFile($file){
		if(file_exists($file)){
			$data = file_get_contents($file);
			$data = json_decode($data ,true);
			$namespace = $data["namespace"];
			$data = $data["app-route"];
			foreach ($data as $router) {
				$xd = self::add_named_url($namespace,$router);

				$router["type"] = isset($router["type"]) ? explode(",", $router["type"]) : [] ;
				if(!$xd || in_array("no-route", $router["type"]) ) continue;
				self::AddRoute($router["method"],$xd["route"],$xd["handler"]);
			}
		} else {
			return false;
		}
	}

	/**
	 * Clear All Routes
	 */
	public static function Clear(){
		self::$_get = array();
		self::$_post = array();
		self::$_mix = array();
	}

	/**
	* Dispatch The Route
	*/
	public static function Dispatch(){
		$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
				$r->base_uri(self::$base_uri);
				foreach (self::$_get as $get) { $r->addRoute("GET",$get["route"],$get["handler"]); }
				foreach (self::$_post as $post) { $r->addRoute("POST",$post["route"],$post["handler"]); }
				foreach (self::$_mix as $mix) { $r->addRoute($mix["method"],$mix["route"],$mix["handler"]); }
			},[ 'cacheFile' => self::$_cache ]
		);
		$httpMethod = $_SERVER['REQUEST_METHOD'];
		$uri = rtrim($_SERVER['REQUEST_URI'] ,"/");
		if($uri == URL::base_uri()) $uri .= "/";
		if (false !== $pos = strpos($uri, '?')) { $uri = substr($uri, 0, $pos); }
		$uri = rawurldecode($uri);

		self::$_status["EXECUTED"] = true;
		$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
		switch ($routeInfo[0]) {
		    case FastRoute\Dispatcher::NOT_FOUND:
				self::$_status["SUCCESS"] = false;
				self::$_status["ERROR"] = 404;
		        return [
					"success"	=>false,
					"error"		=>404
				];
		        break;
		    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
		        $allowedMethods = $routeInfo[1];
				self::$_status["SUCCESS"] = false;
				self::$_status["ERROR"] = 405;
				return [
					"success"	=>false,
					"error"		=>405,
					"allowedMethods"=>$allowedMethods,
				];
		        break;
		    case FastRoute\Dispatcher::FOUND:
		        $handler = $routeInfo[1];
		        $vars = $routeInfo[2];
				self::$_status["SUCCESS"] = true;
				self::$_status["ERROR"] = false;
				return [
					"success" => true,
					"handler" => $handler,
					"parameters"=> $vars
				];
		        break;
		}
	}

	public static function CreateCache($files){
		$_post = [];
		$_get  = [];
		$_mix  = [];

		foreach ($files as $file) {
			if(file_exists($file)){
				$data = file_get_contents($file);
				$data = json_decode($data ,true);
				$namespace = $data["namespace"];
				$data = $data["app-route"];
				foreach ($data as $router) {
					if(is_array($router["method"])){
						array_push($_mix,[
							"method"	=>	$router["method"],
							"route"		=>	$router["route"],
							"handle"	=>	$router["handler"]
						]);
					} else {
						switch ($router["method"]) {
							case 'GET':
									array_push($_get,[
										"route"		=>	$router["route"],
										"handler"	=> $router["handler"]
									]);
								break;
							case 'POST':
									array_push($_post,[
										"route"		=>	$router["route"],
										"handler"	=> $router["handler"]
									]);
								break;
						}
					}
				}
			}
		}
		return [
			"get"	=>	$_get,
			"post"	=>	$_post,
			"mix"	=>	$_mix,
		];
	}

	public static function UseCache($file){
		if(file_exists($file)){
			$data = json_decode(file_get_contents($file),true);
			self::$_get = array_merge(self::$_get,$data["get"]);
			self::$_post = array_merge(self::$_post,$data["post"]);
			self::$_mix = array_merge(self::$_mix,$data["mix"]);
		}
	}
}

ROUTE::SetBaseUri(URL::base_uri());

?>
