<?php
namespace maple\cms\plugin;
use \maple\cms\DB;
use \maple\cms\SECURITY;
use \maple\cms\database\Schema;

/**
 * Setup class
 * @since 1.0
 * @package Maple CMS
 * @subpackage Maple CMS Plugin
 */
class SETUP{
	const columns = [
		"options" =>	[
			"id"		=>	[ "primary" => true, "auto-increment"	=>	true, "type" =>	"int", ],
			"name"		=>	[ "type" => "varchar", "length" => 50, "unique"	=> "true", ],
			"value"		=>	[ "type" => "text", ],
		],
		"pages" =>	[
			"id"		=>	[ "primary" => true, "auto-increment"	=>	true, "type" =>	"int", ],
			"name"		=>	[ "type" => "varchar", "length" => 50, "unique"	=> "true", ],
			"title"		=>	[ "type" => "varchar", "length" => 150, ],
			"content"	=>	[ "type" => "text", ],
			"created"	=>	[ "type" => "datetime", ],
			"modified"	=>	[ "type" => "datetime", ],
			"author"	=>	[ "type" =>	"int", ],
			"url"		=>	[ "type" => "varchar", "length" => 150, ],
		],
	];

	private static $db = null;

	private static function verify_existing_table($table){
		$missing = [];
		if(!self::$db->columns_exists(array_keys(self::columns[$table]),$missing)){
			foreach ($missing as $column) self::$db->add_column($column,self::columns[$table][$column]);
		}
	}

	/**
	 * initialize setup
	 * @uses maple\cms\DB::initialized()
	 */
	public static function initialize(){
		self::$db = new Schema();
		foreach (self::columns as $table => $schema) {
			if(self::$db->table_exists($table)){
				self::$db->table($table);
				self::verify_existing_table($table);
			} else {
				self::$db->create($table);
				foreach($schema as $column => $attributes) self::$db->add_column($column,$attributes);
			}
			self::$db->save();
		}
	}

	public static function install(){
		if(!DB::_()->has("options",[ "name"	=>	"site:name" ])) DB::_()->insert("options",[ "name"	=>	"site:name", "value" => "Maple Website" ]);
		if(!DB::_()->has("options",[ "name"	=>	"site-owner:name" ])) DB::_()->insert("options",[ "name"	=>	"site-owner:name", "value" => "Maple" ]);
		if(!DB::_()->has("options",[ "name"	=>	"site-owner:link" ])) DB::_()->insert("options",[ "name"	=>	"site-owner:link", "value" => "http://maple.rubixcode.com" ]);
		if(!DB::_()->has("options",[ "name"	=>	"theme" ])) DB::_()->insert("options",[ "name"	=>	"theme", "value" => "maple/theme/default" ]);
	}
}

SETUP::initialize();
?>
