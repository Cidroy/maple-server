<?php
namespace maple\cms\database;
use \maple\cms\DB;

/**
 * Schema Builder
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class Schema{
	/**
	 * Commands Stack
	 * @var array
	 */
	private $stack;
	/**
	 * Connection Database
	 * Defaults to global link
	 * @var sql link
	 */
	private $database;
	/**
	 * execution index
	 * @var integer
	 */
	private $index;
	/**
	 * Database engine
	 * default : InnoDB
	 * @var string
	 */
	public $engine = "InnoDB";

	function __construct($database = null){
		if($database === null) $database = DB::_();

		$this->index = 0;
		$this->stack = [
			"create"	=>	false,
			"table"		=>	false,
			"add_column"	=>	[],
			"drop_column"=>	[],
			"rename_table"=>	[],
			"rename_column"=>[],
			"drop"		=>	[],
			"truncate"	=>	[],
			"drop_if_exist"=>	[],
			"primary"	=> false,
			"insert"	=>	false,
		];

		$this->database = $database;
	}

	private function __f1(){
		$in = [];
		$_column = [
			"COLUMN_DEFAULT"	=>	null,
			"IS_NULLABLE"		=>	"NO",
			"DATA_TYPE"			=>	"varchar",
			"CHARACTER_MAXIMUM_LENGTH"	=>	false,
			"COLUMN_KEY"		=>	"",
			"EXTRA"				=>	""
		];
		foreach ($this->stack["add_column"] as $key => $value) {
			$temp = [];
			if( isset($value["default"]) )	$temp["COLUMN_DEFAULT"]				=	$value["default"];
			if( isset($value["nullable"]) )	$temp["IS_NULLABLE"]				=	$value["nullable"] ? "YES" : "NO";
			if( isset($value["type"]) )		$temp["DATA_TYPE"]					=	$value["type"];
			if( isset($value["length"]) )	$temp["CHARACTER_MAXIMUM_LENGTH"]	=	$value["length"];
			if( isset($value["primary"]) )	$temp["COLUMN_KEY"]					=	$value["primary"] ? "PRI" : "" ;
			if( isset($value["auto-increment"]) )	$temp["EXTRA"]				=	$value["auto-increment"] ? "auto_increment" : "" ;
			$temp = array_merge( $_column , array_merge($value,$temp) );

			$in[] = implode(" ",[
				"`{$key}`",
				$temp["DATA_TYPE"].($temp["CHARACTER_MAXIMUM_LENGTH"]?"({$temp["CHARACTER_MAXIMUM_LENGTH"]})":""),
				"".($temp["IS_NULLABLE"] == "NO"? "NOT NULL":"NULL"),
				"".($temp["EXTRA"] == "auto_increment" ? "AUTO_INCREMENT" : "")
			]);

			if($temp["COLUMN_KEY"] == "PRI") $this->stack["primary"] = $key;
		}
		$this->stack["add_column"] = [];
		return $in;
	}

	/**
	 * Create new Sql Table
	 * @api
	 * @throws \InvalidArgumentException if $table is not of type 'string'
	 * @param  string $table Table Name
	 * @return $this
	 */
	public function create($table){
		if(!is_string($table)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);

		$this->stack["create"]	= $table;
		$this->stack["table"]	= $table;
		return $this;
	}

	/**
	 * Select Table
	 * @api
	 * @throws \InvalidArgumentException if $table is not of type 'string'
	 * @param  string $table table name
	 * @return $this
	 */
	public function table($table){
		if(!is_string($table)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		$this->stack["table"]		= $table;
		return $this;
	}

	/**
	 * Check if table exists
	 * @api
	 * @throws \InvalidArgumentException if $table is not of type 'string'
	 * @param  string $table table name
	 * @return boolean        status
	 */
	public function table_exists($table){
		if(!is_string($table)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		return $this->database->query("SHOW TABLES LIKE '{$this->database->prefix()}{$table}'")->fetch();
	}

	/**
	 * Check if coulmn(s) exist
	 * @api
	 * @throws \InvalidArgumentException if $column not of type 'string' or 'array'
	 * @param  mixed[string,array]  $columns column name
	 * @param  array $missing missing list
	 * @return boolean           status
	 */
	public function columns_exists($columns,&$missing = false){
		if(is_string($columns)) $columns = [$columns];
		if(!is_array($columns)) throw new \InvalidArgumentException("Argument #1 must be of type 'string' or 'array'", 1);

		$existing = array_keys(
						$this->table_exists($this->stack["table"])?
						$this->database->columns($this->stack["table"]):
						$this->stack["columns"]
					);
		$missing = array_diff($columns,$existing);
		return !$missing;
	}

	/**
	 * Add a column to table
	 * @api
	 * @throws \InvalidArgumentException if $column and $param is not of type 'string' and 'array' respectively
	 * @param string $column  column name
	 * @param array $param column details
	 * @return $this
	 */
	public function add_column($column,$param){
		if(!is_string($column)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #2 shoud be of type 'array'", 1);
		if( !isset($this->stack["add_column"][$column]) ) $this->stack["add_column"][$column] = $param;
		else $this->stack["add_column"][$column] = array_merge($this->stack["add_column"][$column],$param);
		return $this;
	}

	/**
	 * Drop a column from table
	 * @api
	 * @throws \InvalidArgumentException if $column is not of type 'string'
	 * @param string $column  column name
	 * @return $this
	 */
	public function drop_column($column){
		if(!is_string($column)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		$this->stack["drop_column"][] = $column;
		return $this;
	}

	/**
	 * Alter column in table
	 * @api
	 * @throws \InvalidArgumentException if $column is not of type 'string'
	 * @throws \InvalidArgumentException if $param is not of type 'array'
	 * @param string $column  column name
	 * @param array $param  column details
	 * @return $this
	 */
	public function alter($column,$param){
		if(!is_string($column)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		if(!is_array($param)) throw new \InvalidArgumentException("Argument #2 shoud be of type 'array'", 1);
		if( !isset($this->stack["alter"][$column]) ) $this->stack["alter"][$column] = $param;
		else $this->stack["alter"][$column] = array_merge($this->stack["alter"][$column],$param);
		return $this;
	}

	/**
	 * Rename a table
	 * @api
	 * @throws \InvalidArgumentException if $name or $new is not of type 'string'
	 * @param string $name  table name
	 * @param string $new  new table name
	 * @return $this
	 */
	public function rename_table($name,$new){
		if(!is_string($name)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		if(!is_string($new)) throw new \InvalidArgumentException("Argument #2 shoud be of type 'string'", 1);
		$this->stack["rename_table"][$name] = $new;
		return $this;
	}

	/**
	 * Rename Column
	 * @api
	 * @throws \InvalidArgumentException if $name or $new is not of type 'string'
	 * @param  string $name column name
	 * @param  string $new  new name
	 * @return $this
	 */
	public function rename_column($name,$new){
		if(!is_string($name)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		if(!is_string($new)) throw new \InvalidArgumentException("Argument #2 shoud be of type 'string'", 1);
		$this->stack["rename_column"][$name] = $new;
		return $this;
	}

	/**
	 * Drop Table
	 * @api
	 * @throws \InvalidArgumentException if $table is not of type 'string'
	 * @param  string $table table name
	 * @return $this
	 */
	public function drop($table){
		if(!is_string($table)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		$this->stack["drop"][] = $table;
		return $this;
	}

	/**
	* Truncate Table
	* @api
	* @throws \InvalidArgumentException if $table is not of type 'string'
	* @param  string $table table name
	* @return $this
	*/
	public function truncate($table){
		if(!is_string($table)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		$this->stack["truncate"][] = $table;
		return $this;
	}

	/**
	* Drop Table if Exists
	* @api
	* @throws \InvalidArgumentException if $table is not of type 'string'
	* @param  string $table table name
	* @return $this
	*/
	public function drop_if_exist($table){
		if(!is_string($table)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'string'", 1);
		$this->stack["drop_if_exist"][] = $table;
		return $this;
	}

	/**
	 * Insert Data into the table
	 * @api
	 * @throws \InvalidArgumentException if $data is not of type 'array'
	 * @param  array $data data
	 * @return $this
	 */
	public function insert($data){
		if(!is_array($data)) throw new \InvalidArgumentException("Argument #1 shoud be of type 'array'", 1);
		$this->stack["insert"] = $data;
		return $this;
	}

	/**
	 * Save the Schema to database
	 * @api
	 * @return array {
	 *         @type array 'queries'
	 *         @type array 'errors'
	 * }
	 */
	public function save(){
		$stack = [];
		if($this->stack["create"]){
			$in = $this->__f1();
			if($this->stack["primary"]) $in[] = "PRIMARY KEY (`{$this->stack["primary"]}`)";
			$this->stack["primary"] = false;
			$in = implode(",",$in);
			$in = "CREATE TABLE `{$this->database->prefix()}{$this->stack["create"]}` ({$in}) ENGINE = {$this->engine}
			";
			$this->stack["table"]	= $this->stack["create"];
			$this->stack["create"] = false;
			$this->index += 1;
			$stack[$this->index]  = $in;
		} else {
			$in = $this->__f1();
			foreach ($in as $value) {
				$this->index += 1;
				$stack[$this->index] = "ALTER TABLE `{$this->database->prefix()}{$this->stack["table"]}` ADD {$value}";
			}
		}

		if($this->stack["insert"]){
			$this->index += 1;
			$stack[$this->index]  = $this->database->debug()->insert( $this->stack["table"] , $this->stack["insert"] );
		}

		foreach ($this->stack["truncate"] as $table){
			$this->index += 1;
			$stack[$this->index] = "TRUNCATE `{$this->database->prefix()}{$table}`";
		}
		$this->stack["truncate"] = [];

		foreach ($this->stack["drop"] as $table){
			$this->index += 1;
			$stack[$this->index]  = "DROP TABLE `{$this->database->prefix()}{$table}`";
		}
		$this->stack["drop"] = [];

		$error = [];
		$this->database->pdo->beginTransaction();
		foreach ($stack as $query) {
			$this->database->query($query);
			$__t_error = $this->database->error();
			if($__t_error[1]) $error[] = $__t_error;
		}
		$this->database->pdo->commit();
		return [
				"queries"	=>	$stack,
				"errors"	=>	$error
		];
	}

	/**
	 * Roll Back Recent Changes
	 * @api
	 */
	public function roll_back(){
		$this->database->pdo->rollBack();
	}

	public function backup($table,$destination = false){
		$stack = $this->database->backup($table);
		if($destination){
			file_put_contents($destination,json_encode($stack));
		}
		return $stack;
	}

	/**
	 * Restore data from table backup array
	 * @param  file-path $source source of backup file
	 * @return array         save status
	 */
	public function restore($source){
		$back = null;
		if(is_array($source)) $back = $source ;
		else $back = json_decode(file_get_contents($source),true);
		$this->create($back["schema"]["table"]);
		foreach ($back["schema"]["columns"] as $key => $value) {
				$this->add_column($key,$value);
			$this->insert($back["values"]);
		}
		return $this->save();
	}

}
?>
