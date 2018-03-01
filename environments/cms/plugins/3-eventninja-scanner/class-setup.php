<?php
namespace eventninjaz;

use \maple\cms\database\Schema;
use \maple\cms\DB;
use maple\cms\SECURITY;
use maple\cms\FILE;

class SETUP{
	const tables = [
		"evz_rh_scanned"	=>	[
			"id"		=>	[ "primary" => true, "auto-increment"	=>	true, "type" =>	"int", ],
			"post_id"	=>	[ "type"	=> "text" ],
			"time"		=>	[ "type"	=> "datetime", ],
			"by"		=>	[ "type"	=> "int" ],
			"client"	=>	[ "type"	=> "text" ],
			"client_mail"=>	[ "type"	=> "text" ],
		],
		"evz_rh_user"	=>	[
			"id"		=>	[ "primary" => true, "auto-increment"	=>	true, "type" =>	"int", ],
			"name"		=>	[ "type" => "varchar", "length" => 50 ],
			"email"		=>	[ "type" => "varchar", "length" => 50, "unique"	=> "true", ],
			"created"	=>	[ "type" => "datetime", ],
		]
	];

	// Reusable Database Schema Object
	private static $db = null;

	/**
	 * Verify if the tables exists then they have correct columns
	 * NOTE : This does not check for data type of column
	 * @author Rinzler D. Vicky <vicky@rubixcode.com>
	 * @param array $table table detail
	 * @return void
	 */
	private static function verify_existing_table($table){
		$missing = [];
		if(!self::$db->columns_exists(array_keys(self::tables[$table]),$missing)){
			foreach ($missing as $column) self::$db->add_column($column,self::tables[$table][$column]);
		}
	}

	/**
	 * Initialize and add required data tables to database
	 * @author Rinzler D. Vicky <vicky@rubixcode.com>
	 * @return void
	 */
	public static function initialize(){
		self::$db = new Schema();
		foreach (self::tables as $table => $schema) {
			if(self::$db->table_exists($table)){
				self::$db->table($table);
				self::verify_existing_table($table);
			} else {
				self::$db->create($table);
				foreach($schema as $column => $attributes) self::$db->add_column($column,$attributes);
			}
			self::$db->save();
		}
		try{
			if(!file_exists(__DIR__ . "/config.json"))
			FILE::write(__DIR__."/config.json",[
				"table" => [
					"prefix"	=>	"wp_",
					"ticket"	=>	"posts",
					"client"	=>	"users",
					"plugin"	=>	"event_magic_tickets"
				]
			]);
		}catch(\Exception $e){}
	}

	/**
	 * Run the Installation
	 * @author Rinzler D. Vicky <vicky@rubixcode.com>
	 * @return void
	 */
	public static function install(){
	}
}

SETUP::initialize();
?>