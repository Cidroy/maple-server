<?php
namespace maple\cms;

/**
 * Site Details Class
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class SITE{

	/**
	 * Site related details
	 * @var array
	 */
	private static $details = [
		"name"	=>	"Maple CMS",
		"owner"	=>	[
			"name"	=>	"Rubixcode",
			"link"	=>	"https://rubixcode.com"
		]
	];

	/**
	 * Save initialization status
	 * @var boolean
	 */
	private static $_initialized = false;

	/**
	 * Initialize Site Class
	 * @api
	 * @uses DB::initialized
	 * @uses DB::_
	 * @throws \RuntimeException if DB class is not initialized;
	 */
	public static function initialize(){
		if(!DB::initialized()) throw new \RuntimeException("'\\maple\\cms\\DB' was not initialized", 1);
		self::$details = [
			"name"	=>	current(DB::_()->select("options","*",["name" => "site:name"]))["value"],
			"owner"	=>	[
					"name"	=>	current(DB::_()->select("options","*",["name" => "site-owner:name"]))["value"],
					"link"	=>	current(DB::_()->select("options","*",["name" => "site-owner:link"]))["value"],
				]
		];
		self::$_initialized = true;
	}

	/**
	 * Return initialization status
	 * @api
	 * @return boolean status
	 */
	public static function initialized() { return self::$_initialized; }

	/**
	 * return site name
	 * @api
	 * @return string site name
	 */
	public static function name(){ return self::$details["name"]; }

	/**
	 * return site owner details
	 * @api
	 * @throws \InvalidArgumentException if $attr not of type 'string'
	 * @param  string $attr detail name
	 * @return string       detail
	 */
	public static function owner($attr){
		if(!is_string($attr)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		return isset(self::$details["owner"][$attr])?self::$details["owner"][$attr]:false;
	}

	/**
	 * Rename Site
	 * @api
	 * @throws \InvalidArgumentException if $name is not of type 'string'
	 * @param  string $name New Name
	 */
	public static function rename($name){
		if(!is_string($name)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		DB::_()->update("options",[ "value"	=>	$name ],["name" => "site:name"]);
		return DB::_()->rowCount()!=0;
	}

	/**
	 * Edit Site Owner Details
	 * @api
	 * @throws \InvalidArgumentException if $attr or $value is not of type 'string'
	 * @throws \DomainException if $attr is not a valid Site attribute
	 * @param  string $attr  Owner Attribute
	 * @param  string $value value
	 * @return boolean        status
	 */
	public static function edit_owner($attr,$value){
		if(!is_string($attr)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);
		if(!is_string($value)) throw new \InvalidArgumentException("Argument #2 should be of type 'string'", 1);
		if(!in_array(array_keys($attr,self::$details["owner"]))) throw new \DomainException("Invalid Attribute", 1);
		DB::_()->update("options",[ "value"	=>	$value ],["name" => "site-owner:{$attr}"]);
		return DB::_()->rowCount()!=0;
	}
}
?>
