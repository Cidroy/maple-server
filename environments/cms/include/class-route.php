<?php
namespace maple\cms;

/**
 * Router class
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class ROUTER{
	/**
	 * Dispatch status
	 * @var integer
	 */
	const NOT_DISPATCHED = 0;
	const NOT_FOUND = 1;
	const METHOD_NOT_ALLOWED = 2;
	const FOUND = 3;
	/**
	 * Vendor Location
	 * @var file-path
	 */
	const _router_vendor_file = \ROOT.\VENDOR."/FastRoute/autoload.php";
	/**
	 * Router Cache File
	 * @var file-path
	 */
	const _route_cache = \ROOT.\CACHE."/router";

	/**
	 * Internal static router
	 * @var object router
	 */
	private static $_router = null;
	/**
	 * Dispatch Status
	 * @var array
	 */
	private static $_status = null;
	/**
	 * Save initialization status
	 * @var boolean
	 */
	private static $_initialized = false;

	/**
	 * Router sources
	 * @var array
	 */
	private static $_sources = [];

	/**
	 * Router Files used
	 * @var array
	 */
	private static $_used_file = [];

	/**
	 * Return new Router object
	 * @return object router
	 */
	public static function object(){ return new __router(); }

	/**
	 * Initialize CMS Router
	 * @api
	 */
	public static function initialize(){
		if(!file_exists(self::_router_vendor_file)) throw new \maple\cms\exceptions\VendorMissingException("Unable to load vendor for 'router'", 1);
		require_once self::_router_vendor_file;
		if(!file_exists(self::_route_cache)) mkdir(self::_route_cache,0777,true);
		self::$_router = self::object();
		foreach (self::$_sources as $namespace => $files)
			foreach ($files as $file )
				self::use_file($namespace,$file);
		$cache = "router-".CACHE::unique().".cache";
		self::$_router->cache($cache);
		self::$_router->base_uri(URL::base_uri());
		self::$_initialized = true;
	}

	/**
	 * return initialization status
	 * @return boolean status
	 */
	public static function initialized() { return self::$_initialized; }
	/**
	 * add GET router
	 * @api
	 * @param  string $route   url
	 * @param  string $handler function name
	 */
	public static function get($route,$handler){ return self::$_router->get($route,$handler); }
	/**
	 * add POST router
	 * @api
	 * @param  string $route   url
	 * @param  string $handler function name
	 */
	public static function post($route,$handler){ return self::$_router->post($route,$handler); }
	/**
	 * add router
	 * @api
	 * @param  mixed[string,array] $method	method
	 * @param  string $route   url
	 * @param  string $handler function name
	 */
	public static function add_route($method,$route,$handler){ return self::$_router->add_route($method,$route,$handler); }
	/**
	 * add router groups
	 * @api
	 * @param  string $group   url
	 * @param  array $routers function name
	 */
	public static function add_group($group,$routers){ return self::$_router->add_group($group,$routers); }
	/**
	 * return content from dipatch
	 * @api
	 * @return string content
	 */
	public static function dispatch(){ return self::$_router->content(); }
	/**
	 * return dipatch status
	 * @api
	 * @return integer status
	 */
	public static function status(){ return self::$_router->status(); }

	/**
	 * Add Named Router Sources
	 * @api
	 * @throws \InvalidArgumentException if $param is not of type 'array'
	 * @param  array $param namespace => file
	 */
	public static function sources($param){
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #1 must be of type 'array'", 1);
		self::$_sources = array_merge($param,self::$_sources);
	}

	/**
	 * Use file for router
	 * @api
	 * @uses URL::add_named_url
	 * @throws \InvalidArgumentException if $file is not of type 'string'
	 * @param  string $namespace router namespace
	 * @param  string $file file-path
	 * @return boolean       status
	 */
	public static function use_file($namespace,$file){
		if(!is_string($file)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(in_array($file,self::$_used_file)) return true;
		if(!file_exists($file)) return false;
		$routes = json_decode(file_get_contents($file),true);
		if(!is_array($routes)) return false;
		// TODO : add caching
		foreach ($routes as $name => $details) {
			try {
				$route = URL::add_named_url($namespace,$name,$details);
				$type = isset($details["type"])?explode("|",$details["type"]):[];
				if(!$route || in_array("no-route",$type) || !isset($details["handler"]) || !$details["handler"]) continue;
				self::add_route((isset($details["method"])?$details["method"]:"GET"),$route,$details["handler"]);
			} catch (\Exception $e) {
				Log::error([
					"error"	=>	$e->getMessage(),
					"router"=>	[
						"namespace"	=>	$namespace,
						"name"		=>	$name,
						"details"		=>	$details,
					]
				]);
			}
		}
		return true;
	}

	/**
	 * Debug Info
	 * @return array debug values
	 */
	public static function debug(){ if(\DEBUG) return self::$_router->debug(); }
}

/**
 * Router Object Class
 * @since 1.0
 * @access private
 * @uses nikic/FastRoute
 * @package Maple CMS
 * @author Rubixcode
 */
class __router{
	/**
	 * Base Uri
	 * @var string
	 */
	private $_base_uri = "";
	/**
	 * Routes Store
	 * @var array
	 */
	private $_routes = [
		"get"	=> [],
		"post"	=> [],
		"mix"	=> [],
		"groups"=> [],
	];
	/**
	 * Disptcher Object
	 * @var object FastRoute\simpleDispatcher | FastRoute\cachedDispatcher
	 */
	private $_dispatcher = null;
	/**
	 * Store cache details
	 * @var array
	 */
	private $_cache = [
		"active"	=> false,
	];
	/**
	 * Router Info
	 * @var array
	 */
	private $_info = [
		"status"	=>	ROUTER::NOT_DISPATCHED,
		"success"	=>	false,
		"respose"	=>	500,
	];

	/**
	 * Set Base URI
	 * @api
	 * @throws \InvalidArgumentException if $base is not of type 'string'
	 * @param  string $base uri
	 */
	public function base_uri($base){
		if(!is_string($base)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		$this->_base_uri = rtrim($base ,"/");
	}

	/**
	 * Add GET router
	 * @api
	 * @throws \InvalidArgumentException if $route or $handler is not of type 'string'
	 * @param  string $route   router
	 * @param  string $handler callable function name
	 */
	public function get($route,$handler){
		if(!is_string($route)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		if(!is_string($handler)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 1);
		$this->_routes["get"][] = [
			"route"	=>	$route,
			"handler"	=>	$handler,
		];
	}

	/**
	 * Add POST router
	 * @api
	 * @throws \InvalidArgumentException if $route or $handler is not of type 'string'
	 * @param  string $route   router
	 * @param  string $handler callable function name
	 */
	public function post($route,$handler){
		if(!is_string($route)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		if(!is_string($handler)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 1);
		$this->_routes["post"][] = [
			"route"	=>	$route,
			"handler"	=>	$handler,
		];
	}

	/**
	 * Add router
	 * @api
	 * @throws \DomainException if invalid $method
	 * @throws \InvalidArgumentException if $route or $handler is not of type 'string'
	 * @param  mixed[string,array] $method   method
	 * valid methods :
	 * - get
	 * - post
	 * - mixed[]
	 * @param  string $route   router
	 * @param  string $handler callable function name
	 */
	public function add_route($method,$route,$handler){
		if(!is_string($method) && !is_array($method)) throw new \InvalidArgumentException("Argument #1 should be of type 'string' or 'array'", 1);
		if(!is_string($route)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 1);
		if(!is_string($handler)) throw new \InvalidArgumentException("Argument #3 should be of type 'string'", 1);
		$method = strtolower($method);
		if(is_array($method)){
			$this->_routes["mix"][] = [
				"method"	=>	$method,
				"route"		=>	$route,
				"handler"	=>	$handler,
			];
		} else {
			switch ($method) {
				case 'get': $this->get($route,$handler); break;
				case 'post': $this->post($route,$handler); break;
				default: throw new \DomainException("Invalid Argument #1", 1); break;
			}
		}
	}

	/**
	 * Add Route Group
	 * @api
	 * @throws \InvalidArgumentException if $group is not of type 'string'
	 * @throws \InvalidArgumentException if $routes is not of type 'array'
	 * @param string $group  group url
	 * @param array $routes route desciption {
	 *    @type array {
	 *          @type mixed[string,array] 'method' => method
	 *          @type string 'route' => route
	 *          @type string 'handler' => handler
	 *    }
	 * }
	 */
	public function add_group($group,$routes){
		if(!is_string($group)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		if(!is_array($routes)) throw new \InvalidArgumentException("Argument #2 should be of type 'array'", 1);
		if(!isset($this->_routes["group"][$group])) $this->_routes["group"][$group] = [];
		$this->_routes["group"][$group] = array_merge($this->_routes["group"][$group],$routes);
	}

	/**
	 * Activate Cache and store it in file
	 * @api
	 * @throws \InvalidArgumentException if $file is not of type 'string'
	 * @param  string $file cache file name
	 */
	public function cache($file){
		if(!is_string($file)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		$this->_cache = [
			"active"	=> true,
			"file"		=>	ROUTER::_route_cache."/{$file}",
		];
	}

	/**
	 * Return Dispatcher object
	 * @api
	 * @return object \FastRoute\simpleDispatcher | FastRoute\cachedDispatcher
	 */
	public function dispatcher(){
		if($this->_dispatcher === null){
			$this->_dispatcher =
			$this->_cache["active"]?
			\FastRoute\cachedDispatcher(function(\FastRoute\RouteCollector $r){
				$r->base_uri($this->_base_uri);
				foreach ($this->_routes["groups"] as $group => $routes) {
					$r->addGroup($group,function(\RouteCollector $r){
						foreach ($routes as $mix)
							$r->addRoute($mix["method"],$mix["route"],$mix["handler"]);
					});
				}
				foreach ($this->_routes["get"] as $get) { $r->addRoute("GET",$get["route"],$get["handler"]); }
				foreach ($this->_routes["post"] as $post) { $r->addRoute("POST",$post["route"],$post["handler"]); }
				foreach ($this->_routes["mix"] as $mix) { $r->addRoute($mix["method"],$mix["route"],$mix["handler"]); }
				},[
				"cacheFile"	=>	$this->_cache["file"]
			]):
			\FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r){
				$r->base_uri($this->_base_uri);
				foreach ($this->_routes["group"] as $group => $routes) {
					$r->addGroup($group,function(\RouteCollector $r){
						foreach ($routes as $mix)
							$r->addRoute($mix["method"],$mix["route"],$mix["handler"]);
					});
				}
				foreach ($this->_routes["get"] as $get) { $r->addRoute("GET",$get["route"],$get["handler"]); }
				foreach ($this->_routes["post"] as $post) { $r->addRoute("POST",$post["route"],$post["handler"]); }
				foreach ($this->_routes["mix"] as $mix) { $r->addRoute($mix["method"],$mix["route"],$mix["handler"]); }
			});
		}
		return $this->_dispatcher;
	}
	/**
	 * Dispatch the Router
	 * @api
	 * @return array details
	 */
	public function dispatch(){
		$httpMethod = $_SERVER['REQUEST_METHOD'];
		$uri = $_SERVER['REQUEST_URI'];
		if (substr($uri, 0, strlen($this->_base_uri)) == $this->_base_uri) $uri = substr($uri, strlen($this->_base_uri));
		if (false !== $pos = strpos($uri, '?')) { $uri = substr($uri, 0, $pos); }
		$uri = rtrim($uri ,"/");
		if(!$uri)	$uri = "/";
		$uri = rawurldecode($uri);
		$routeInfo = $this->dispatcher()->dispatch($httpMethod, $uri);
		switch ($routeInfo[0]) {
			case \FastRoute\Dispatcher::NOT_FOUND:
				return $this->_info = [
					"success"	=>	false,
					"status"	=>	ROUTER::NOT_FOUND,
					"respose"	=>	404,
				];
				break;
			case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				return $this->_info = [
					"success"	=>	false,
					"status"	=>	ROUTER::METHOD_NOT_ALLOWED,
					"respose"	=>	405,
					"allowed"	=>$routeInfo[1],
				];
				break;
			case \FastRoute\Dispatcher::FOUND:
				return $this->_info = [
					"success"	=>	true,
					"status"	=>	ROUTER::FOUND,
					"handler"	=>	$routeInfo[1],
					"arguments"	=>	$routeInfo[2],
					"respose"	=>	200,
				];
				break;
		}
	}

	/**
	 * Return Content.
	 * If Router not dispatched then self deploys
	 * @return string content
	 */
	public function content(){
		if($this->_info["status"] === ROUTER::NOT_DISPATCHED) $this->dispatch();
		$content = "";
		if($this->_info["status"] === ROUTER::FOUND){
			ob_start();
			$content = call_user_func($this->_info["handler"],$this->_info["arguments"]);
			ob_end_clean();
		}
		return $content;
	}

	/**
	 * return Dispatch status
	 * @api
	 * @return integer dispatch status
	 */
	public function status(){ return $this->_info["status"]; }


	/**
	 * Debug Info
	 * @return array debug info
	 */
	public function debug(){
		return [
			"routes"=>	$this->_routes,
			"info"	=>	$this->_info,
		];
	}
}
?>
