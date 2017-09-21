<?php
namespace maple\cms;

/**
 * Language Class
 * @since 1.0
 * @package Maple CMS
 */
class LANGUAGE {
	/**
	 * Current Language
	 * @var string language code
	 */
	private static $current = "EN";

	/**
	 * Language sources
	 * @var array "namespace" => "folder"
	 */
	private static $sources = [];

	/**
	 * Set The Current Session Language
	 * @api
	 * @param string $lang language code
	 * @return boolean status
	 * false if language is not supported
	 */
	public static function set($lang){
		return true;
	}

	/**
	 * Add a souce folder to namespace
	 * @api
	 * @param string $namespace app namespace
	 * @param string $folder    absolute folder path
	 * @return boolean status
	 */
	public static function add_source($namespace,$folder){
		if(!is_string($namespace)) throw new \InvalidArgumentException( "Argument #1 must be of type 'string'");
		if(!is_string($folder)) throw new \InvalidArgumentException( "Argument #2 must be of type 'string'");

		if(!file_exists($folder) || !is_dir($folder)) return false;
		self::$sources[$namespace] = $folder;
		return true;
	}

	/**
	 * Add Multiple App Namespace and Sources at once
	 * @param array $sources
	 *        @type string app-namespace => absolute folder path
	 */
	public static function add_sources($sources){
		if(!is_array($sources)) throw new \InvalidArgumentException( "Argument 1 must be of type 'string'");
		self::$sources = array_merge(self::$sources, $sources);
	}

	/**
	 * Translate Language
	 * BUG : does nothing
	 * @api
	 * @throws \InvalidArgumentException if Argument #1,#2 or #3 is not of type 'string'
	 * @param  string $namespace namespace
	 * @param  string $string    string name
	 * @param  string $fallback  fallback string
	 * @return string            translated language
	 */
	public static function translate($namespace,$string,$fallback = ""){
		if($fallback) return $fallback;
		else return $string;
	}
}

?>
