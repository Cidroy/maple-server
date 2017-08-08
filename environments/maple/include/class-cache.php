<?php
/**
* this is the class that handles page caching based on demand
*/

require_once ROOT.INC."/Vendor/Stash/autoload.php";

// TODO : Maple Cache Class
class CACHE{
	private static $_cache_hash  = null;
	private static $_cache_mime  = null;
	private static $_cache_setting = [
		"life"	=>	3600,
		"user-specific" => false
	];
	private static $_cache_store = [];
	private static $_cache_pool = [];

	public static function pool($namespace){
		if(!self::$_cache_hash){
			self::$_cache_hash = implode("", SECURITY::get_permissions())."000".MAPLE::UserDetail("ID");
		}
		if(!array_key_exists($namespace,self::$_cache_pool)){
			$driver = new Stash\Driver\FileSystem([]);
			$pool = new Stash\Pool($driver);
			self::$_cache_pool[$namespace] = [
				"driver"=> $driver,
				"pool"	=> $pool,
			];
		}
		return self::$_cache_pool[$namespace];
	}

	public static function put($namespace,$key,$value,$param = []){
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		if($param["user-specific"]) $key = self::$_cache_hash.$key;
		$pool->save(
			$pool->getItem($key)
				 ->expiresAfter($param["life"])
				 ->set($value)
		);
	}

	public static function has($namespace,$key,$param = []){
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		if($param["user-specific"]) $key = self::$_cache_hash.$key;
		return $pool->getItem($key)->isHit();
	}


	public static function get($namespace,$key,$default=null,$param = []){
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		$_key = $key;
		if($param["user-specific"]) $key = self::$_cache_hash.$key;
		return self::has($namespace,$_key,$param)?$pool->getItem($key)->get():$default;
	}

	public static function remember($namespace,$key,$default=null,$param = []){
		if(!self::has($namespace,$key,$param)){
			self::put($namespace,$key,$param);
		}
		return self::get($namespace,$key,$default,$param);
	}

	public static function delete($namespace,$key,$param = []){
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		if($param["user-specific"]) $key = self::$_cache_hash.$key;
		$pool->deleteItem($key);
	}

	public static function clear($namespace,$param = []){
		$param = array_merge(self::$_cache_setting,$param);
		if($param["user-specific"]) $key = self::$_cache_hash.$key;
		self::pool($namespace)["pool"]->clear();
	}

	public static function purge($namespace,$param = []){
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		if($param["user-specific"]) $key = self::$_cache_hash.$key;
		$pool->purge();
	}

	public static function get_mime_http_header($mime){
		if(!self::$_cache_mime){
			$file = __DIR__."/config/cache.json";
			self::$_cache_mime = json_decode(file_get_contents($file),true);
		}
		return isset(self::$_cache_mime[$mime]) ? self::$_cache_mime[$mime] : self::$_cache_mime["default"];
	}

	public static function set_mime_http_header($mime,$param){
		$file = __DIR__."/config/cache.json";
		self::get_mime_http_header("default");
		self::$_cache_mime[$mime] = $param;
		ENVIRONMENT::lock("maple/cms : cache-update-mime-type");
			file_put_contents($file, json_encode(self::$_cache_mime));
		ENVIRONMENT::unlock();
	}

	public static function delete_mime_http_header($mime){
		self::get_mime_http_header("default");
		unset(self::$_cache_mime[$mime]);
		self::set_mime_http_header("none","");
	}

	public static function get_all_mime_http_header(){
		self::get_mime_http_header("default");
		return self::$_cache_mime;
	}

	public static function get_location($cache_store){
		if(!self::$_cache_store){
			$file = __DIR__."/config/cache-store.json";
			self::$_cache_store = json_decode(file_get_contents($file),true);
		}
		return isset(self::$_cache_store[$cache_store]) ? self::$_cache_store[$cache_store] : false;
	}

	public static function add_location($cache_store,$location){
		$file = __DIR__."/config/cache-store.json";
		self::get_location("none");
		self::$_cache_store[$cache_store] = $location;
		ENVIRONMENT::lock("maple/cms : cache-add-source");
			file_put_contents($file,json_encode (self::$_cache_store));
		ENVIRONMENT::unlock();
	}

	public static function remove_location($cache_store){
		self::get_location("none");
		unset(self::$_cache_store[$cache_store]);
		self::add_location("none","");
	}
}
?>
