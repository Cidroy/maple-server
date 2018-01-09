<?php
namespace maple\cms;
/**
* Caching utility
* @since 1.0
* @uses tedivm/Stash
* @package Maple CMS
* @author Rubixcode
*/
class CACHE{
	/**
	 * Vendor Location
	 * @var file-path
	 */
	const _cache_vendor = \ROOT.\VENDOR."/Stash";
	/**
	 * Stores initialization status
	 * @var boolean
	 */
	private static $_initialied = false;
	/**
	 * Caching default settings
	 * @var array {
	 *      @type integer 'life' in seconds
	 *      @type boolean 'user-specific' to specify cache to specific user
	 * }
	 */
	private static $_cache_setting = [
		"life"	=>	3600,
		"user-specific" => false
	];
	/**
	 * Cache namespace
	 * @var array
	 */
	private static $_cache_pool = [];
	/**
	 * Unique Cache Hash for the user
	 * @var string
	 */
	private static $_cache_hash  = null;
	/**
	 * initialize cache class
	 * @api
	 * @throws \RuntimeException if SESSION not initialized
	 */
	public static function initialize(){
		// BUG: Does nothing
		if(!file_exists(self::_cache_vendor)) throw new \maple\cms\exceptions\VendorMissingException("Vendor 'Stash' missing.", 1);
		if(!SESSION::active()) throw new \RuntimeException("'\\maple\\cms\\SESSION' not initialized", 1);

		self::$_cache_hash = SESSION::get("maple/cache","key");
		if(!self::$_cache_hash){
			self::$_cache_hash = md5("maple-".USER::access_level().USER::id());
			SESSION::set("maple/cache","key",self::$_cache_hash);
		}
		require_once self::_cache_vendor."/autoload.php";
		self::$_initialied = true;
	}
	/**
	 * Return user specific hash key
	 * @return string hash
	 */
	public static function unique(){ return self::$_cache_hash; }
	/**
	 * return initialization status
	 * @return boolean status
	 */
	public static function initialized(){ return self::$_initialied; }

	/**
	 * Return Cache Pool with namespace.
	 * if cache pool does not exist then it creates one
	 * @api
	 * @throws \InvalidArgumentException if $namespace is not of type 'string'
	 * @throws \InvalidArgumentException if $param is not of type 'array'
	 * @param  string $namespace namespace
	 * @param  array  $params    optional.
	 * @return object            \Stash\Pool
	 */
	public static function pool($namespace,$params = []){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_array($params)) throw new \InvalidArgumentException("Argument #2 must be of type 'array'", 1);

		if(!array_key_exists($namespace,self::$_cache_pool)){
			$driver = new \Stash\Driver\FileSystem([]);
			$pool = new \Stash\Pool($driver);
			self::$_cache_pool[$namespace] = [
				"driver"=> $driver,
				"pool"	=> $pool,
			];
		}
		return self::$_cache_pool[$namespace];
	}
	/**
	 * Put data into Pool
	 * @api
	 * @throws InvalidArgumentException if $namespace is not of type 'string'
	 * @throws InvalidArgumentException if $key is not of type 'string'
	 * @throws InvalidArgumentException if $param is not of type 'array'
	 * @param  string $namespace namespace
	 * @param  string $key       cache key
	 * @param  mixed[] $value     value
	 * @param  array  $param     parameters
	 */
	public static function put($namespace,$key,$value,$param = []){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($key)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #4 must be of type 'array'", 1);
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		if(isset($param["user-specific"]) && $param["user-specific"]) $key = self::$_cache_hash.$key;
		$pool->save(
			$pool->getItem($key)
				 ->expiresAfter($param["life"])
				 ->set($value)
		);
	}
	/**
	 * Check if Cache exists
	 * @api
	 * @throws InvalidArgumentException if $namespace is not of type 'string'
	 * @throws InvalidArgumentException if $key is not of type 'string'
	 * @throws InvalidArgumentException if $param is not of type 'array'
	 * @param  string  $namespace namespace
	 * @param  string  $key       cache key
	 * @param  array   $param     parameters
	 * @return boolean            status
	 */
	public static function has($namespace,$key,$param = []){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($key)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #3 must be of type 'array'", 1);
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		if(isset($param["user-specific"]) && $param["user-specific"]) $key = self::$_cache_hash.$key;
		return $pool->getItem($key)->isHit();
	}
	/**
	 * Get cache.
	 * if not exists then return the default value passed.
	 * @api
	 * @throws InvalidArgumentException if $namespace is not of type 'string'
	 * @throws InvalidArgumentException if $key is not of type 'string'
	 * @throws InvalidArgumentException if $param is not of type 'array'
	 * @param  string $namespace namespace
	 * @param  string $key       key
	 * @param  mixed[] $default   defaul values
	 * @param  array  $param     parameters
	 * @return mixed[]            value
	 */
	public static function get($namespace,$key,$default=null,$param = []){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($key)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #4 must be of type 'array'", 1);
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		$_key = $key;
		if(isset($param["user-specific"]) && $param["user-specific"]) $key = self::$_cache_hash.$key;
		return self::has($namespace,$_key,$param)?$pool->getItem($key)->get():$default;
	}
	/**
	 * Remember Cache.
	 * Saves if not exists
	 * @api
	 * @throws InvalidArgumentException if $namespace is not of type 'string'
	 * @throws InvalidArgumentException if $key is not of type 'string'
	 * @throws InvalidArgumentException if $param is not of type 'array'
	 * @param  string $namespace namespace
	 * @param  string $key       cache key
	 * @param  mixed[] $default   default value to save
	 * @param  array  $param     parameters
	 * @return mixed[]            values
	 */
	public static function remember($namespace,$key,$default=null,$param = []){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($key)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #4 must be of type 'array'", 1);
		if(isset($param["user-specific"]) && $param["user-specific"]) $key = self::$_cache_hash.$key;
		if(!self::has($namespace,$key,$param)) self::put($namespace,$key,$param);
		return self::get($namespace,$key,$default,$param);
	}
	/**
	 * Delete Cache
	 * @api
	 * @throws InvalidArgumentException if $namespace is not of type 'string'
	 * @throws InvalidArgumentException if $key is not of type 'string'
	 * @throws InvalidArgumentException if $param is not of type 'array'
	 * @param  string $namespace namespace
	 * @param  string $key       cache key
	 * @param  array  $param     parameters
	 */
	public static function delete($namespace,$key,$param = []){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($key)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #3 must be of type 'array'", 1);
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		if(isset($param["user-specific"]) && $param["user-specific"]) $key = self::$_cache_hash.$key;
		$pool->deleteItem($key);
	}
	/**
	 * Clear Cache Pool Namespace
	 * @api
	 * @throws InvalidArgumentException if $namespace is not of type 'string'
	 * @throws InvalidArgumentException if $param is not of type 'array'
	 * @param  string $namespace namespace
	 * @param  array  $param     parameters
	 */
	public static function clear($namespace,$param = []){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #2 must be of type 'array'", 1);
		$param = array_merge(self::$_cache_setting,$param);
		if(isset($param["user-specific"]) && $param["user-specific"]) $key = self::$_cache_hash.$key;
		self::pool($namespace)["pool"]->clear();
	}
	/**
	 * Purge Cache Pool Namespace
	 * @api
	 * @throws InvalidArgumentException if $namespace is not of type 'string'
	 * @throws InvalidArgumentException if $param is not of type 'array'
	 * @param  string $namespace namespace
	 * @param  array  $param     parameters
	 */
	public static function purge($namespace,$param = []){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #2 must be of type 'array'", 1);
		$param = array_merge(self::$_cache_setting,$param);
		$pool = &self::pool($namespace)["pool"];
		if(isset($param["user-specific"]) && $param["user-specific"]) $key = self::$_cache_hash.$key;
		$pool->purge();
	}
}
?>
