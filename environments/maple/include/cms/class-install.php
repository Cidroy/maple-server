<?php
namespace Maple\Cms;

define("MAXIMUM_SQL_BACKUPS",5);

class INSTALLER{

	private static $_backup_location = "backup";

	private static function _f1(&$param){
		$missing = array_diff_key([
			"Plugin"	=>	false,
			"Path"		=>	false,
			"Activate"	=>	false,
			"Deactivate"=>	false
		],$param);

		if($missing){
			return [
				"type"	=>	"error",
				"title"	=>	"missing parameter",
				"message" => implode(" ",$missing)
			];
		}

		$param = array_merge([
			"Backup"	=>	false,
		],$param);

		$param["Plugin"] = \URL::dir($param["Plugin"]);

		return false;
	}

	public static function Activate($param){

		$x = self::_f1($param);
		if($x)	return $param;

		require_once(\URL::dir("{$param["Plugin"]}/{$param["Path"]}"));

		$used_backup = false;
		if($param["Backup"]){
			$file = new \_FILE("{$param["Plugin"]}/backup/backup.json");
			if($file->exists()){
				$backups = json_decode($file->read(),true);
				$bfile = end($backups);
				$file = new \_FILE("{$param["Plugin"]}/backup/{$bfile}");
				if($file->exists()){

					$schema = new \DB\Schema(\DB::_());
					$data 	= json_decode($file->read(),true);

					foreach ($data["backup"]["tables"] as $table) {
						var_dump($schema->restore($table));
					}

					$x = [
						"title"		=>	"Plugin was installed from Backup",
						"message"	=>	"Used backup created on ".\TIME::now()->timestamp(key($backups))->format('l jS \\of F Y h:i:s A'),
						"type"		=>	"success"
					];
					\MAPLE::DashMessage($x);
					$used_backup = true;
					return $x;
				} else {
					$x = [
						"title"		=>	"Plugin was not restored from backup",
						"message"	=>	"Tried using backup created on ".\TIME::now()->timestamp(key($backups))->format('l jS \\of F Y h:i:s A'),
						"type"		=>	"warning"
					];
					\MAPLE::DashMessage($x);
				}
			}
		}
		if(!$used_backup){
			$x = call_user_func($param["Activate"],$param);
			if(is_array($x) && isset($x["Tables"])){
				$schema	 = new \DB\Schema();
				$ret = [];
				foreach ($x["Tables"] as $table => $columns) {
					$schema->create($table);
					foreach ($columns as $cname => $cparam) $schema->addColumn($cname,$cparam);
					$ret[] = $schema->save();
				}
				if(\DEBUG){
					$errors = [];
					foreach ($ret as $value) {
						if($value["Errors"]) $errors[] = implode(",",$value["Errors"]);
					}
					\MAPLE::DashMessage([
						"message"	=>	"<pre>".json_encode($errors,JSON_PRETTY_PRINT)."</pre>",
						"type"		=>	"debug"
					]);
				}
			}
		}
	}

	public static function Deactivate($param){
		$x = self::_f1($param);
		if($x)	return $param;

		require_once(\URL::dir("{$param["Plugin"]}/{$param["Path"]}"));

		$tables = call_user_func($param["Deactivate"],$param);
		$param["tables"] = $tables;
		$data = [
			"type"	=>	"success",
			"action"=>	"drop",
			"drops" =>	$tables
		];
		if($param["Backup"]){
			$data = self::Backup($param);
			$_backup_location = self::$_backup_location;
			$file = new \_FILE("{$param["Plugin"]}/{$_backup_location}/backup.json");
			$dest = "";
			$read = [];
			if( $file->exists()){ $read = json_decode($file->read(),true); }
			if(count($read) >= MAXIMUM_SQL_BACKUPS ){
				reset($read);
				$del = key($read);
				$__tfile = new \_FILE("{$param["Plugin"]}/{$_backup_location}/{$read[$del]}");
				if($__tfile->exists())	$__tfile->delete();
				unset($read[$del]);
			}
			$time = time();
			$dest = "sql-backup-{$time}.json";
			$read[$time] = $dest;
			$file->write(json_encode($read));
			unset($file);
			$file = new \_FILE("{$param["Plugin"]}/{$_backup_location}/{$dest}");
			$file->write($data);
			\MAPLE::DashMessage([
				"title"		=>	"Created Plugin Backedup",
				"message"	=>	"Backup created on time : ".\TIME::now()->timestamp($time)->format('l jS \\of F Y h:i:s A'),
				"type"		=>	"success"
			]);
		}
		$schema = new \DB\Schema(\DB::_());
		foreach ($tables["tables"] as $table){
			$schema->drop($table);
		}
		$data["sql-faults"] = $schema->save();
		return $data;
	}

	public static function Backup($param){
		$x = self::_f1($param);
		if($x)	return $param;
		require_once(\URL::dir("{$param["Plugin"]}/{$param["Path"]}"));

		$tables = $param["tables"];

		$res =  [
			"tables"	=>	[]
		];
		foreach ($tables["tables"] as $table) {
			$res["tables"][] = \DB::_()->backup($table);
		}
		return [
			"type"	=>	"success",
			"action"=>	"backup",
			"backup"=>	$res
		];
	}

}
?>
