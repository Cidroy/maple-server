<?php
namespace DB;

class Schema{
	private $stack;
	private $database;
	private $do_stack;
	private $index;

	public $engine = "InnoDB";

	function __construct($database = null){
		if($database === null) $database = \DB::_();

		$this->index = 0;
		$this->stack = [
			"create"	=>	false,
			"table"		=>	false,
			"addColumn"	=>	[],
			"dropColumn"=>	[],
			"renameTable"=>	[],
			"renameColumn"=>[],
			"drop"		=>	[],
			"truncate"	=>	[],
			"dropIfExist"=>	[],
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
		foreach ($this->stack["addColumn"] as $key => $value) {
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
		$this->stack["addColumn"] = [];
		return $in;
	}

	public function create($table){
		$this->stack["create"]	= $table;
		$this->stack["table"]	= $table;
		return $this;
	}

	public function table($table){
		$this->stack["table"]		= $table;
		return $this;
	}

	public function tableExists($table){
		return $database->do_query("SELECT 1 FROM `{$table}` LIMIT 1");
	}

	public function addColumn($name,$param){
		if( !isset($this->stack["addColumn"][$name]) ) $this->stack["addColumn"][$name] = $param;
		else $this->stack["addColumn"][$name] = array_merge($this->stack["addColumn"][$name],$param);
		return $this;
	}

	public function dropColumn($column){
		$this->stack["dropColumn"][] = $column;
		return $this;
	}

	public function alter($name,$param){
		if( !isset($this->stack["alter"][$name]) ) $this->stack["alter"][$name] = $param;
		else $this->stack["alter"][$name] = array_merge($this->stack["alter"][$name],$param);
		return $this;
	}

	public function renameTable($name,$param){
		$this->stack["renameTable"][$name] = $param;
		return $this;
	}

	public function renameColumn($name,$param){
		$this->stack["renameColumn"][$name] = $param;
		return $this;
	}

	public function drop($table){
		$this->stack["drop"][] = $table;
		return $this;
	}

	public function truncate($table){
		$this->stack["truncate"][] = $table;
		return $this;
	}

	public function dropIfExist($table){
		$this->stack["dropIfExist"][] = $table;
		return $this;
	}

	public function insert($data){
		$this->stack["insert"] = $data;
		return $this;
	}

	public function save(){
		$stack = [];
		if($this->stack["create"]){
			$in = $this->__f1();
			if($this->stack["primary"]) $in[] = "PRIMARY KEY (`{$this->stack["primary"]}`)";
			$this->stack["primary"] = false;
			$in = implode(",",$in);
			$in = "CREATE TABLE `{$this->database->prefix}{$this->stack["create"]}` ({$in}) ENGINE = {$this->engine}
			";
			$this->stack["table"]	= $this->stack["create"];
			$this->stack["create"] = false;
			$this->index += 1;
			$stack[$this->index]  = $in;
		} else {
			$in = $this->__f1();
			foreach ($in as $value) {
				$this->index += 1;
				$stack[$this->index] = "ALTER TABLE `{$this->database->prefix}{$this->stack["table"]}` ADD {$value}";
			}
		}

		if($this->stack["insert"]){
			$this->index += 1;
			$stack[$this->index]  = $this->database->debug()->insert( $this->stack["table"] , $this->stack["insert"] );
		}

		foreach ($this->stack["truncate"] as $table){
			$this->index += 1;
			$stack[$this->index] = "TRUNCATE `{$this->database->prefix}{$table}`";
		}
		$this->stack["truncate"] = [];

		foreach ($this->stack["drop"] as $table){
			$this->index += 1;
			$stack[$this->index]  = "DROP TABLE `{$this->database->prefix}{$table}`";
		}
		$this->stack["drop"] = [];

		$error = [];
		$this->database->pdo->beginTransaction();
		foreach ($stack as $query) {
			$this->database->do_query($query);
			$__t_error = $this->database->error();
			if($__t_error[1]) $error[] = $__t_error;
		}
		$this->database->pdo->commit();
		return [
				"Queries"	=>	$stack,
				"Errors"	=>	$error
		];
	}

	public function rollBack(){
		$database->pdo->rollBack();
	}

	public function backup($table,$destination = false){
		$stack = $this->database->backup($table);
		if($destination){
			file_put_contents($destination,json_encode($stack));
		}
		return $stack;
	}

	public function restore($source){
		$back = null;
		if(is_array($source)) $back = $source ;
		else $back = json_decode(file_get_contents($source),true);
		$this->create($back["schema"]["table"]);
		foreach ($back["schema"]["columns"] as $key => $value) {
			$this->addColumn($key,$value);
		}
		$this->insert($back["values"]);
		return $this->save();
	}

}
?>
