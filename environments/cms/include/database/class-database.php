<?php
namespace maple\cms;
use \PDO;
/**
 * DB Extender class
 * @since 1.0
 * @package Maple CMS
 * @subpackage Database
 * @author Rubixcode
 */
class __db extends \Medoo\Medoo{

	/**
	 * Return Prefix
	 * @return string prefix
	 */
	public function prefix() { return $this->prefix; }

	/**
	 * return all table names in database
	 * @api
	 * @return array table names
	 */
	public function tables(){
		return $this->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
	}

	public function table_exists($name){
		if(!is_string($name)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		return in_array($this->prefix.$name,$this->tables());
	}

	/**
	 * Get Column names and details
	 * @api
	 * @throws \InvalidArgumentException if $table is not of type 'array'
	 * @param  string $table table name
	 * @return array        column details
	 */
	public function columns($table){
		if(!is_string($table)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		$data = $this->query("SELECT *
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME='{$this->prefix}{$table}'
			AND TABLE_SCHEMA = '{$this->pdo->query('select database()')->fetchColumn()}'
		")->fetchAll();
		$stack = [];
		foreach ($data as $row) {
			$stack[$row["COLUMN_NAME"]] = [
				"ORDINAL_POSITION"			=>  $row["ORDINAL_POSITION"],
				"COLUMN_DEFAULT"			=>  $row["COLUMN_DEFAULT"],
				"IS_NULLABLE"				=>  $row["IS_NULLABLE"],
				"DATA_TYPE"					=>  $row["DATA_TYPE"],
				"CHARACTER_MAXIMUM_LENGTH"	=>  $row["CHARACTER_MAXIMUM_LENGTH"],
				"CHARACTER_OCTET_LENGTH"	=>  $row["CHARACTER_OCTET_LENGTH"],
				"NUMERIC_PRECISION"			=>  $row["NUMERIC_PRECISION"],
				"NUMERIC_SCALE"				=>  $row["NUMERIC_SCALE"],
				"DATETIME_PRECISION"		=>  $row["DATETIME_PRECISION"],
				"CHARACTER_SET_NAME"		=>  $row["CHARACTER_SET_NAME"],
				"COLLATION_NAME"			=>  $row["COLLATION_NAME"],
				"COLUMN_TYPE"				=>  $row["COLUMN_TYPE"],
				"COLUMN_KEY"				=>  $row["COLUMN_KEY"],
				"EXTRA"						=>  $row["EXTRA"],
				"PRIVILEGES"				=>  $row["PRIVILEGES"],
				"COLUMN_COMMENT"			=>  $row["COLUMN_COMMENT"],
				"GENERATION_EXPRESSION"		=>  $row["GENERATION_EXPRESSION"],
			];
		}
		return $stack;
	}

	/**
	 * Create backup array for a table
	 * @api
	 * @throws \InvalidArgumentException if $table is not of type 'string'
	 * @param  string $table table
	 * @return array        backup array
	 */
	public function backup($table){
		if(!is_string($table)) throw new \InvalidArgumentException("Argument #1 should be of type 'string'", 1);

		$columns = $this->columns($table);
		$_columns = array_keys($columns);
		return [
			"schema"	=>	[
				"table"		=>	$table,
				"time"		=>	time(),
				"columns"	=>	$columns
			],
			"values"	=>	$this->select($table,$_columns)
		];
	}

	/**
	 * Restore data from table backup array
	 * @api
	 * @throws \InvalidArgumentException if $backup is not of type 'array'
	 * @throws \DomainException if $backup is not properly formatted
	 * @param  file-path $source source of backup file
	 * @return array         save status
	 */
	public function restore($backup){
		$back = null;
		if(!is_array($backup)) throw new \InvalidArgumentException("Argument #1 should be of type 'array'", 1);
		if(!isset($back["schema"]) || !isset($back["schema"]["table"]) || !isset($back["schema"]["columns"]) || !isset($back["schema"]["values"])) throw new \DomainException("Invalid Argument #1", 1);

		$back = $backup ;
		$this->create($back["schema"]["table"]);
		foreach ($back["schema"]["columns"] as $key => $value) {
				$this->add_column($key,$value);
			$this->insert($back["values"]);
		}
		return $this->save();
	}

}
?>
