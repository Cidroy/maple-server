<?php
class URL{
	private static $base_uri ="";
	private static $_src	= ROOT.INC."config/url.json";
	private static $_pseudo = [];
	private static $_url	= [];
	private static $_dir	= [];
	private static $_cache	= [
		"dir"	=>	[],
		"http"	=>	[],
		"c_path"=>	[],
		"c_url" =>	[],
	];
	private static $_name_url 	= [];
	private static $_PAGES		= [];
	private static $_REQUESTS	= [];
	private static $_sc_url		= [];

	public static function Initialize() {
		$data = json_decode(file_get_contents(self::$_src),true);
		self::$_pseudo	= array_reverse($data["pseudo"]);
		self::$_url		= array_reverse($data["url"]);
		self::$_dir		= array_reverse($data["dir"]);

		self::$base_uri	= ENVIRONMENT::url()->base();
		self::$_url["%ROOT%"] = ENVIRONMENT::url()->root();
		self::$_url["%CURRENT%"] = ENVIRONMENT::url()->current();

		foreach (self::$_dir as $key => $value) { self::$_dir[$key] = defined($value) ? constant($value) : $value; }

		self::sanitize_request();
		self::set_pages();
	}

	public static function sanitize_request(){
		self::$_REQUESTS = $_REQUEST;
		foreach ($_REQUEST as $key => $value) {
			if(!is_array($value));
			// TODO : !important! use current link method
//				$_REQUEST[$key]=htmlspecialchars(mysqli_escape_string(DB::Link(),$value), ENT_QUOTES , 'UTF-8' );
		}
	}

	public static function request($key)	{ return isset(self::$_REQUESTS[$key])?self::$_REQUESTS[$key]:false; }

	public static function base_uri(){ return self::$base_uri; }

	public static function http($path,$query = "") {
		if($query){
			$query = "?".http_build_query($query);
		}
		if(isset(self::$_cache["http"][$path])) return self::$_cache["http"][$path].$query;
		else {
			$url = self::conceal_path($path);
			$url = str_replace(self::$_pseudo,self::$_url,$url);
			self::$_cache["http"][$path] = $url;
			return $url.$query;
		}
	}

	public static function dir($path) {
		if(isset(self::$_cache["dir"][$path])) return self::$_cache["dir"][$path];
		else {
			$dir = str_replace(self::$_pseudo,self::$_dir,self::conceal_url($path));
			self::$_cache["dir"][$path] = $dir;
			return $dir;
		}
	}

	public static function conceal_path($path) {
		if(isset(self::$_cache["c_path"][$path])) return self::$_cache["c_path"][$path];
		else {
			$path = str_replace("\\","/",$path);
			$c = str_replace(self::$_dir,self::$_pseudo,$path);
			self::$_cache["c_path"][$path] = $c;
			return $c;
		}
	}

	public static function conceal_url($path) {
		if(isset(self::$_cache["c_url"][$path])) return self::$_cache["c_url"][$path];
		else {
			$c = str_replace(self::$_url,self::$_pseudo,$path);
			self::$_cache["c_url"][$path] = $c;
			return $c;
		}
	}

	public static function has_request($param,$method='request',$selector="all"){
		$flag = false;
		$array = [];
		$method = strtolower($method);
		switch ($method) {
			case 'request': $array = $_REQUEST; break;
			case 'get': $array = $_GET; break;
			case 'post': $array = $_POST; break;
			//	case 'put': $array = $_PUT; break;
			//	case 'delete': $array = $_DELETE; break;
			default:
			Log::debug('Invalid Argument #2 for a request type in '.__METHOD__,$method);
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
					Log::debug('Invalid Argument #3 for a request type in '.__METHOD__. " expecting values 'all','any','*' ",$method);
					break;
			}
		} else if( is_string($param) ){
			$flag = array_key_exists($param,$array);
		}
		else{
			Log::debug('Invalid Argument #1 for '.__METHOD__." expecting type 'String' or 'Array'",$param);
			$flag = false;
		}
		return $flag;
	}

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
			return "";
		}
	}

	public static function redirect($param){
		header("Location: {$param}");
	}

	public static function add_named_url($namespace,$details){
		// TODO : !important! for plugin , do caching after a plugin has been activated or deactivated
		if(!isset(self::$_name_url[$namespace]))
			self::$_name_url[$namespace] = [];
		if(isset(self::$_name_url[$namespace][$details["name"]])){
			Log::error([
				"error" =>	"Named Url not set because it already exists",
				"namespace"	=> $namespace,
				"name"		=> $details["name"],
				"existing"	=> self::$_name_url[$namespace][$details["name"]],
				"new"		=>	$details
			]);
			return false;
		}
		$x = $details;
		$url = "";
		if(isset($x["base-page"])){
			if(!self::$_sc_url && DB::Connect()){
				$query = DB::Query("SELECT `name`,`Content` FROM `m_pages`");
				while ($row = DB::Fetch_Array($query)){
					foreach (PARSER::get_shortcodes($row["Content"]) as $sc)
						foreach ($sc["attrs"] as $key => $value)
							self::$_sc_url["{$key}@{$value}"] = $row["name"];
				}
			}
			if(self::$_sc_url && isset(self::$_sc_url[$x["base-page"]]))
				$url .=	"/".self::$_sc_url[$x["base-page"]];
			else $url .= "/".$x["base-page"];
		}
		if(isset($x["url"])){
			$url  = rtrim(str_replace(self::http("%ROOT%"), "/", self::http($x["url"])),"/");
			$x["route"] = isset($x["route"]) ? $x["route"] : "";
		}
		$url .= $x["route"];
		while(isset($x["parent"])){
			$x = self::$_name_url[$namespace][$x["parent"]];
			$url = $x["route"].$url;
		}
		self::$_name_url[$namespace][$details["name"]] = [
			"route" 	=>	$url,
			"query"		=>	isset($details["query"])?$details["query"]:[],
			"handler"   =>	$details["handler"],
		];
		return self::$_name_url[$namespace][$details["name"]];
	}

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
						"handler"=>	$details["handler"]
					];
				}
			}
		}
		return ["name-url" => $_name_url];
	}

	// TODO : testing
	public static function use_name_cache($file){
		if(file_exists($file)){
			self::$_name_url = array_merge(
				json_decode(file_get_contents($file),true),
				self::$_name_url
			);
		}
	}

	// this stores the breakdown of the fakepath
	public static function set_pages(){
		// TODO : How do I get the url from browser?
		$temp=str_ireplace(self::$base_uri,'',$_SERVER['REQUEST_URI'] );
		$temp=explode('/',explode('?',$temp)[0]);
		self::$_PAGES=array_filter($temp);
	}

	public static function page($int){ return isset(self::$_PAGES[$int])?self::$_PAGES[$int]:false ; }
}

URL::Initialize();

 ?>
