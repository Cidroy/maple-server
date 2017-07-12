<?php
require_once ROOT.INC."/Vendor/Medoo/autoload.php";
require_once ROOT.INC."/Vendor/Medoo/class-schema.php";
use Medoo\Medoo;
/**
 * class DB for data base connection
 * this is provided with the intention to provide safe mysql connection.
 * this provides simplicity in change of SQL plugin acros the Maple Framework
 * @package Maple Framework
 */
class DB  extends Medoo {
	/**
	 * This is the mysqli link for database connection
	 * @default false : no-connection
	 * @value mysqli.link : connection to proper host
	 */
	private static $conn = false;

	private static $database = false;

	/**
	 * Connect to the database defined in m-config.php
	 * If connected it return true or
	 * If connection fails it returns false and logs the error into DEBUG
	 * It can be used to auto-connect if there is no connection to the SQL Server
	 * @return bool
	 */
	public static function Connect(){
		if(!self::$conn){
			self::$conn = mysqli_connect(_DB('SERVER'),_DB('USER'),_DB('PASSWORD'),_DB('DB'));
			$errno=mysqli_connect_errno(self::$conn);
			if($errno)Log::debug("SQL Error",$errno);
			if(self::$database === false) {
				self::$database = new DB([
					'database_type' => 'mysql',
					'database_name' => _DB("DB"),
					'server' => _DB('SERVER'),
					'username' => _DB('USER'),
					'password' => _DB('PASSWORD'),
					'charset' => 'utf8',
					'prefix'	=>	_DB('PREFIX')
				]);
			}
		}
		return self::$conn?true:false;
	}

	/**
	 * This is an equivalent of mysqli_query.
	 * @param string : sanitinzed sql query
	 * @return sql.object
	 */
	public static function Query($query){
		self::Connect();
		return mysqli_query(self::$conn,$query);
	}

	/**
	 * Return the active SQL connection link.
	 * It returns false if no connection is established
	 * @return mysql.link | false
	 */
	public static function Link(){
		self::Connect();
		return self::$conn;
	}

	/**
	 * This is an equivalent of mysqli_fetch_array
	 * This returns the rows yielded from a query
	 * @param sql.query.result
	 * @return sql.query.rows
	 */
	public static function Fetch_Array($Fetch_Array){
		return mysqli_fetch_array($Fetch_Array);
	}

	/**
	 * Close the current SQL connection
	 * will be done only if there is an established connection to SQL.
	 */
	public static function Close(){ if(self::$conn) mysqli_close(self::$conn); }

	public static function GetTable($table){ return "m_$table"; }

	public static function _(){
		if(!self::$database) self::Connect();
		return self::$database;
	}
}
?>
