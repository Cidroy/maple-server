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
	private static $_initialied = false;
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
	 * BUG : does not test database failure
	 * @filter database|failed when database does not connected
	 */
	private static function connect(){
		self::$_details = include_once(self::_configuration_file);
		self::$_object = self::object(self::$_details["database"]);
		MAPLE::do_filters("databse|initialized");
		// MAPLE::do_filters("databse|failed");
	}

	/**
	 * Initialize the class and attempt conntection
	 * @api
	 * @throws \maple\cms\exceptions\VendorMissingException if Vendor 'Medoo' is missing
	 * @throws \maple\cms\exceptions\SqlConnectionException if SQL connection is not available or not set
	 */
	public static function initialize(){
		if(!file_exists(self::_vendor_location."/autoload.php")) throw new \maple\cms\exceptions\VendorMissingException("Please install vendor 'Medoo'", 1);
		if(!file_exists(self::_configuration_file))	throw new \maple\cms\exceptions\SqlConnectionException("System Not Configured", 1);

		require_once self::_vendor_location."/autoload.php";
		require_once ROOT.INC."/database/class-database.php";
		MAPLE::add_autoloader("\\maple\\cms\\database\\Schema",ROOT.INC."/database/class-schema.php");
		self::connect();
		self::$_initialied = true;
	}

	/**
	 * Return Initialization status
	 * @api
	 * @return boolean status
	 */
	public static function initialized(){ return self::$_initialied; }

	/**
	 * @api
	 * @see Medoo Documents for functions
	 * @param  mixed[] $param
	 * @return Medoo        object
	 */
	public static function object($param = null){ return new __db($param); }

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

?>
