<?php
namespace maple\cms;
use \ROOT; use \VENDOR; use \__MAPLE__; use \INC;


/**
 * Database ORM Class
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class DB{
	/**
	 * Configuration file that contains database details
	 * @var file-path
	 */
	const _configuration_file = ROOT.__MAPLE__."/configurations.php";
	/**
	 * Vendor Location
	 * @var file-path
	 */
	const _vendor_location =  ROOT.VENDOR."/Medoo";
	/**
	 * Initialization Status
	 * @var boolean
	 */
	private static $_initialized = false;
	/**
	 * Database Object
	 * @var Medoo
	 */
	private static $_object = false;
	/**
	 * Database details
	 * @var array
	 */
	private static $_details = false;

	/**
	 * Connect and Create a static object
	 * @filter database|initialized when database is connected properly
	 * @filter database|failed when database does not connected
	 */
	private static function connect(){
		self::$_details = include_once(self::_configuration_file);
		self::$_object = self::object(self::$_details["database"]);
		if(isset(self::$_object->pdo)){
			MAPLE::do_filters("databse|initialized");
			return true;
		} else {
			MAPLE::do_filters("databse|failed");
			return false;
		}
	}

	/**
	 * Initialize the class and attempt conntection
	 * @api
	 * @throws \maple\cms\exceptions\SqlConnectionException if SQL connection is not available or not set
	 */
	public static function initialize($obj = null){
		self::$_initialized = false;
		if($obj instanceof __db and $obj !== false){
			self::$_object = $obj;
			self::$_initialized = true;
		}
		else if(!file_exists(self::_configuration_file))	throw new \maple\cms\exceptions\SqlConnectionException("System Not Configured", 1);
		else self::$_initialized = self::connect();
		if(!self::$_initialized) throw new \RuntimeException("Database connection failed", 2);
	}

	/**
	 * load the basic classes needed
	 * @api
	 * @throws \maple\cms\exceptions\VendorMissingException if Vendor 'Medoo' is missing
	 */
	public static function load(){
		if(!file_exists(self::_vendor_location."/autoload.php")) throw new \maple\cms\exceptions\VendorMissingException("Please install vendor 'Medoo'", 1);
		require_once self::_vendor_location."/autoload.php";
		require_once ROOT.INC."/database/class-database.php";
	}

	/**
	 * Return Initialization status
	 * @api
	 * @return boolean status
	 */
	public static function initialized(){ return self::$_initialized; }

	/**
	 * @api
	 * @see Medoo Documents for functions
	 * @param  mixed[] $param
	 * @return Medoo        object
	 */
	public static function object($param = null){ return new __db($param); }

	/**
	 * Return an alternative version of same database
	 *
	 * @param array $param additional modifications
	 * @return Medoo object
	 */
	public static function modified($param = []){
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #1 must be of type 'array'", 1);
		return self::object(array_merge(self::$_details["database"],$param));
	}

	/**
	 * Return \PDO Object with passed SQL Query
	 * @api
	 * @param  string $query sql query
	 * @return object        PDO Object
	 */
	public static function query($query){ return self::$_object->query($query); }

	/**
	 * Fetch All Array from PDO Result
	 * @api
	 * @param  object $object PDO Object
	 * @return array         result
	 */
	public static function fetch_array($object){ return $object->fetchAll(); }
	/**
	 * use advanced functionality
	 * @return object
	 */
	public static function _(){ return self::$_object; }
}

DB::load();
?>
