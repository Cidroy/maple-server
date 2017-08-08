<?php
namespace maple\cms;
use \ENVIRONMENT;
use \ROOT;
use \INC;
use \CONFIG;

/**
 * Url class for url manipulation
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class URL{
	/**
	 * Configuration location of urls
	 * @var file-path
	 */
	const src	= ROOT.CONFIG."/url.json";
	/**
	 * Base Uri of Site
	 * @var string
	 */
	private static $base_uri ="";
	/**
	 * psudo names
	 * @var array
	 */
	private static $_pseudo = [];
	/**
	 * Urls for psudo names
	 * @var array
	 */
	private static $_url	= [];
	/**
	 * Paths for pseudo names
	 * @var array
	 */
	private static $_dir	= [];
	/**
	 * temporary cache
	 * @var array
	 */
	private static $_cache	= [
		"dir"	=>	[],
		"http"	=>	[],
		"c_path"=>	[],
		"c_url" =>	[],
	];
	/**
	 * named urls
	 * namespace => name => url
	 * @var array
	 */
	private static $_name_url 	= [];
	/**
	 * Pages
	 * @var array
	 */
	private static $_PAGES		= [];
	/**
	 * Unsanitized request
	 * @var array
	 */
	private static $_REQUESTS	= [];
	/**
	 * Short code urls
	 *
	 * @var array
	 */
	private static $_shortcode_url= [];
	/**
	 * store iniitialization status
	 * @var boolean
	 */
	private static $_initialized = false;

	public static function initialize() {
		try {
			if(!file_exists(self::src)) self::diagnose();
			$data = json_decode(file_get_contents(self::src),true);
			self::$_pseudo	= array_reverse($data["pseudo"]);
			self::$_url		= array_reverse($data["url"]);
			self::$_dir		= array_reverse($data["dir"]);

			self::$base_uri	= ENVIRONMENT::url()->base();
			self::$_url["%ROOT%"] = ENVIRONMENT::url()->root();
			self::$_url["%CURRENT%"] = ENVIRONMENT::url()->current();

			foreach (self::$_dir as $key => $value) { self::$_dir[$key] = defined($value) ? constant($value) : $value; }

			self::sanitize_request();
			self::set_pages();

			self::$_initialized = true;
		} catch (\Exception $e) {
			Log::error($e->getMessage());
			throw $e;
		}
	}

	/**
	 * return iniitialization status
	 * @return boolean status
	 */
	public static function initialized(){ return self::$_initialized; }

	/**
	 * returns unsanitized $_REQUEST values
	 * @api
	 * @throws \InvalidArgumentException if $key is not string
	 * @param  string $key request key
	 * @return mixed[string,Array]      value
	 * - false if does not exists
	 */
	public static function request($key)	{
		if(!is_string($key)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		return isset(self::$_REQUESTS[$key])?self::$_REQUESTS[$key]:false;
	}

	/**
	 * Base Uri
	 * @api
	 * @return string base uri
	 */
	public static function base_uri(){ return self::$base_uri; }

	/**
	 * Convert a file path or concealed Path to its respective url
	 * @api
	 * @throws \InvalidArgumentException if $path is not string or $query is not string or array
	 * @param  string $path  file path or concealed path
	 * @param  mixed[string,array] $query query parametes
	 * @return string        url
	 */
	public static function http($path,$query = "") {
		if(!is_string($path)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if($query &&( !is_string($query) || !is_array($query) )) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);

		if($query) $query = "?".http_build_query($query);
		if(isset(self::$_cache["http"][$path])) return self::$_cache["http"][$path].$query;
		else {
			$url = self::conceal_path($path);
			$url = str_replace(self::$_pseudo,self::$_url,$url);
			self::$_cache["http"][$path] = $url;
			return $url.$query;
		}
	}

	/**
	 * Convert a url or concealed Path to its respective file path
	 * @api
	 * @throws \InvalidArgumentException if $path is not string
	 * @param  string $path  url or concealed path
	 * @return string        file path
	 */
	public static function dir($path) {
		if(!is_string($path)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(isset(self::$_cache["dir"][$path])) return self::$_cache["dir"][$path];
		else {
			$dir = str_replace(self::$_pseudo,self::$_dir,self::conceal_url($path));
			self::$_cache["dir"][$path] = $dir;
			return $dir;
		}
	}

	/**
	 * Convert a file Path to its respective concealed path
	 * @api
	 * @throws \InvalidArgumentException if $path is not string
	 * @param  string $path  file path
	 * @return string        concealed path
	 */
	public static function conceal_path($path) {
		if(!is_string($path)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(isset(self::$_cache["c_path"][$path])) return self::$_cache["c_path"][$path];
		else {
			$path = str_replace("\\","/",$path);
			$c = str_replace(self::$_dir,self::$_pseudo,$path);
			self::$_cache["c_path"][$path] = $c;
			return $c;
		}
	}

	/**
	 * Convert a url to its respective concealed url
	 * @api
	 * @throws \InvalidArgumentException if $path is not string
	 * @param  string $url  url
	 * @return string        concealed url
	 */
	public static function conceal_url($url) {
		if(!is_string($url)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(isset(self::$_cache["c_url"][$url])) return self::$_cache["c_url"][$url];
		else {
			$c = str_replace(self::$_url,self::$_pseudo,$url);
			self::$_cache["c_url"][$url] = $c;
			return $c;
		}
	}

	/**
	 * Check wether a set of request is available for in its request method
	 * @api
	 * @throws \InvalidArgumentException if $param is not of type 'string' or 'array'
	 * @throws \InvalidArgumentException if $method is not of type 'string'
	 * @throws \InvalidArgumentException if $selector is not of type 'string'
	 * @throws \DomainException if $method is not a valid value for $method
	 * @throws \DomainException if $selector is not a valid value for $selector
	 * @param  mixed[string,array]  $param    requests
	 * @param  string  $method   request method
	 * valid values are
	 * - get
	 * - post
	 * - put
	 * - delete
	 * - request
	 * defaults to "request"
	 * @param  string  $selector test for all or test for any
	 * valid values are
	 * - all : tests for all
	 * - * : tests for all
	 * - any : test if any one exists
	 * @return boolean           status based on selector
	 */
	public static function has_request($param,$method='request',$selector="all"){
		if(!is_string($param) && !is_array($param)) throw new \InvalidArgumentException("Argument #1 must be of type 'string' or 'array'", 1);
		if(!is_string($method)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!is_string($selector)) throw new \InvalidArgumentException("Argument #3 must be of type 'string'", 1);

		$flag = false;
		$array = [];
		$method = strtolower($method);
		switch ($method) {
			case 'request': $array = $_REQUEST; break;
			case 'get': $array = $_GET; break;
			case 'post': $array = $_POST; break;
			case 'put': $array = $_PUT; break;
			case 'delete': $array = $_DELETE; break;
			default:
				throw new \DomainException('Invalid Argument #2 for a request type in '.__METHOD__,1);
			break;
		}
		if(is_array($param)){
			switch ($selector) {
				case 'all':
				case '*':
					$flag = true;
					foreach ($param as $value) if(!array_key_exists($value,$array)){ $flag = false; }
					break;
				case 'any':
					$flag = false;
					foreach ($param as $value) if(array_key_exists($value,$array)){ $flag = true; }
				default:
					throw new \DomainException('Invalid Argument #3 for a request type in '.__METHOD__. " expecting values 'all','any','*' ",1);
				break;
			}
		} else if( is_string($param) ){
			$flag = array_key_exists($param,$array);
		}
		return $flag;
	}

	/**
	 * return named url
	 * if not found returns false
	 * @api
	 * @param  string $namespace namespace
	 * @param  string $name      url name
	 * @param  array  $query     queries
	 * @return string            url
	 */
	public static function name($namespace,$name,$query = []){
		if(isset(self::$_name_url[$namespace][$name]["query"]))
			$query = array_merge(self::$_name_url[$namespace][$name]["query"],$query);
		if($query){
			$query = "?".http_build_query($query);
		} else $query = "";
		if(isset(self::$_name_url[$namespace]) && isset(self::$_name_url[$namespace][$name]))
			return rtrim(self::http("%ROOT%"),"/").self::$_name_url[$namespace][$name]["route"].$query;
		else{
			Log::warning("named url \"{$name}\" not found in namespace \"{$namespace}\" ");
			return false;
		}
	}

	/**
	 * Redirect current page to url
	 * @api
	 * @param  string $url full Url
	 */
	public static function redirect($url){ header("Location: {$url}"); }

	/**
	 * Add Named Url
	 * TODO : !important! Bug Testing
	 * @api
	 * @uses DB::connected
	 * @uses SHORTCODE::parse
	 * @throws \InvalidArgumentException if $namespace is not of type 'string'
	 * @throws \InvalidArgumentException if $details is not of type 'array'
	 * @param string $namespace namespace
	 * @param string $name name
	 * @param array $details   url description{
	 *              @type string 'url'
	 *              @type string 'parent'
	 *              @type string 'base'
	 * }
	 * @return boolean status
	 */
	public static function add_named_url($namespace,$name,$details){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($name)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!is_array($details)) throw new \InvalidArgumentException("Argument #3 must be of type 'array'", 1);
		// TODO : !important! for plugin , do caching after a plugin has been activated or deactivated
		if(!isset(self::$_name_url[$namespace]))
			self::$_name_url[$namespace] = [];
		if(isset(self::$_name_url[$namespace][$name])){
			Log::error([
				"error" =>	"Named Url not set because it already exists",
				"namespace"	=> $namespace,
				"name"		=> $details["name"],
				"existing"	=> self::$_name_url[$namespace][$details["name"]],
				"new"		=>	$details
			]);
			return false;
		}
		$details_temp = $details;
		$url = "";
		if(isset($details["base"])){
			if(!self::$_shortcode_url && DB::connected())
				foreach (DB::_()->select("pages",["name","content"]) as $row)	#DB // get slugs for shortcode
					foreach (SHORTCODE::parse($row["content"]) as $shortcode)
						self::$_shortcode_url["{$shortcode->name}"] = self::page_uri($row["name"]);
			if(isset(self::$_shortcode_url[$details["base"]])) $url="/".self::$_shortcode_url[$details["base"]].$url;
			else $url="/".self::http($details["base"]).$url;
		}
		if(isset($details["parent"]) && isset(self::$_name_url[$namespace][$details["parent"]]))
			$url = self::$_name_url[$namespace][$details["parent"]]["url"].$url;
		$details["url"] = rtrim($url.(isset($details["route"])?$details["route"]:""),"/");
		self::$_name_url[$namespace][$name] = $details;
		return self::$_name_url[$namespace][$name]["url"];
	}

	/**
	 * Create cache of name url for performance boosting
	 * TODO : !important!
	 * @api
	 * @param  file-path $files destination for cache
	 * @return array        name url cache
	 */
	public static function create_named_url_cache($files){
		$_name_url = [];
		foreach ($files as $file) {
			if(file_exists($file)){
				$data = json_decode(file_get_contents($file),true);
				$namespace = $data["namespace"];
				$data = $data["app-route"];
				foreach ($data as $details) {
					$x = $details;
					$url= $x["route"];
					while(isset($x["parent"])){
						$x = $_name_url[$namespace][$x["parent"]];
						$url = $x["route"].$url;
					}
					$_name_url[$namespace][$details["name"]] = [
						"route" 	=>	$url,
						"handler"	=>	$details["handler"]
					];
				}
			}
		}
		return ["name-url" => $_name_url];
	}

	/**
	 * Loads named url from cache file
	 * TODO : !important!
	 * BUG : does not return anything
	 * @param  file-path $file source
	 * @return boolean       status
	 */
	public static function use_name_cache($file){
		if(file_exists($file)){
			self::$_name_url = array_merge(
				json_decode(file_get_contents($file),true),
				self::$_name_url
			);
		}
	}

	/**
	 * return the pages set to index
	 * returns false if index is not valid
	 * @api
	 * @param  integer $int page index
	 * @return string      page name
	 */
	public static function page($int){ return isset(self::$_PAGES[$int])?self::$_PAGES[$int]:false ; }

	/**
	 * Return Url for Page
	 * BUG : does nothing
	 * @api
	 * @throws \InvalidArgumentException if $name is not of type 'sstring'
	 * @param  string $name page name
	 * @return string       uri
	 */
	public static function page_uri($name){
		return "";
	}

	/**
	 * initialize url pages for processing
	 */
	private static function set_pages(){
		$temp=str_ireplace(self::$base_uri,'',$_SERVER['REQUEST_URI'] );
		$temp=explode('/',explode('?',$temp)[0]);
		self::$_PAGES=array_filter($temp);
	}

	/**
	 * Solve issues related to url Configuration
	 * @throws \maple\cms\exceptions\FilePermissionException if __CLASS__::src is not writable
	 */
	private static function diagnose(){
		if(!file_exists(self::src)){
			$content = [
				"base"		=>	ENVIRONMENT::url()->base(),
				"dynamic"	=>	true,
				"pseudo"	=>	[],
				"url"		=>	[],
				"dir"		=>	[],
			];
			$content["pseudo"] = [
				"%CURRENT%","%ENCODING%","%ROOT%",
				"%DATA%",
				"%CNT%","%PLG%","%PLUGIN%","%THEME%","%VENDOR%","%CONFIG%","%CACHE%",
				"%INC%","%INCLUDE%",
				"%ADMIN%",
				"%ROOT%%PLG%","%ROOT%%INCLUDE%","%ROOT%%THEME%"
			];
			$content["url"] = [
				"%CURRENT%"  => "",
				"%ENCODING%" => ENVIRONMENT::url()->encoding(),
				"%ROOT%" 	 => "%ENCODING%".ENVIRONMENT::url()->root(false),
				"%DATA%"	 => "/data",
				"%CNT%"	 	 => "",
				"%PLG%"	 	 => "%ROOT%/plugin",
				"%PLUGIN%"	 => "%ROOT%/plugin",
				"%THEME%"	 => "%ROOT%/theme",
				"%VENDOR%"	 => "%ROOT%/vendors",
				"%CONFIG%"	 => "%ROOT%/configurations",
				"%CACHE%"	 => "%ROOT%/cache",
				"%INC%"	 	 => "%ROOT%/include",
				"%INCLUDE%"	 => "%ROOT%/include",
				"%ADMIN%"	 => "%ROOT%/admin",
				"%ROOT%%PLG%"=> "%PLUGIN%",
				"%ROOT%%INCLUDE%"=>"%INCLUDE%",
				"%ROOT%%THEME%"=>"%THEME%"
			];
			$content["dir"] = [
				"%CURRENT%"  => "",
				"%ENCODING%" => "",
				"%ROOT%" 	 => "ROOT",
				"%DATA%"	 => "DATA",
				"%CNT%"	 	 => "CNT",
				"%PLG%"	 	 => "PLG",
				"%PLUGIN%"	 => "%ROOT%%PLG%",
				"%THEME%"	 => "THEME",
				"%VENDOR%"	 => "VENDOR",
				"%CONFIG%"	 => "CONFIG",
				"%CACHE%"	 => "CACHE",
				"%INC%"	 	 => "INC",
				"%INCLUDE%"	 => "%ROOT%%INC%",
				"%ADMIN%"	 => "ADMIN",
				"%ROOT%%PLG%"=> "%PLUGIN%",
				"%ROOT%%INCLUDE%"=> "%INCLUDE%",
				"%ROOT%%THEME%"=> "%THEME%"
			];
			file_put_contents(self::src,json_encode($content));
		}
	}

	/**
	 * Sanitize the $_REQUEST for attack vectors
	 */
	private static function sanitize_request(){
		self::$_REQUESTS = $_REQUEST;
		foreach ($_REQUEST as $key => $value) {
			if(!is_array($value));
			// TODO : !important! use current link method
			// $_REQUEST[$key]=htmlspecialchars(mysqli_escape_string(DB::Link(),$value), ENT_QUOTES , 'UTF-8' );
		}
	}
}
 ?>
