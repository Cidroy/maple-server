<?php
namespace rubixcode\hotel;

class ROOM{

	public static function get_available($param = []){
		$extras = [];
		$rooms = [];
		$prefix = \DB::_()->prefix;
		if(isset($param["type"])) $extras[] = "AND `{$prefix}hr_room_type`.`id` = '{$param['type']}'";
		$extras = implode("\n",$extras);
		$sql = \DB::Query(
			"SELECT `{$prefix}hr_room_type`.`name` AS `type` ,`{$prefix}hr_rooms`.`number`,`{$prefix}hr_rooms`.`id`
			FROM `{$prefix}hr_rooms`
			INNER JOIN `{$prefix}hr_room_type`
			ON `{$prefix}hr_rooms`.`type`=`{$prefix}hr_room_type`.`id`
			WHERE `number` NOT IN
			(SELECT DISTINCT(`number`)
			FROM `{$prefix}hr_rooms`
			JOIN `{$prefix}hr_reservations`
			ON `{$prefix}hr_reservations`.`room`=`{$prefix}hr_rooms`.`id`
			AND (`{$prefix}hr_reservations`.`check_in` < NOW())
			AND (`{$prefix}hr_reservations`.`check_out`> NOW()))
			{$extras}
		");
		while($row = \DB::Fetch_Array($sql)){
			$rooms[] = [
				'number'=> $row['number'],
				'id' 	=> $row['id'],
				'type' 	=> $row['type'],
				'available' => true,
				"url"	=> \URL::name("rubixcode/hotel/admin","rooms-detail",[
					"id"	=>	$row["id"]
				]) ,
			];
		}
		return $rooms;
	}

	public static function get_unavailable($param = []){
		$extras = [];
		$rooms = [];
		$prefix = \DB::_()->prefix;
		if(isset($param["type"])) $extras[] = "AND `{$prefix}hr_room_type`.`id` = '{$param['type']}'";
		$extras = implode("\n",$extras);
		$sql = \DB::Query(
			"SELECT `{$prefix}hr_room_type`.`name` AS `type` ,`{$prefix}hr_rooms`.`number`,`{$prefix}hr_rooms`.`id`
			FROM `{$prefix}hr_rooms`
			INNER JOIN `{$prefix}hr_room_type`
			ON `{$prefix}hr_rooms`.`type`=`{$prefix}hr_room_type`.`id`
			WHERE `number` IN
			(SELECT DISTINCT(`number`)
			FROM `{$prefix}hr_rooms`
			JOIN `{$prefix}hr_reservations`
			ON `{$prefix}hr_reservations`.`room`=`{$prefix}hr_rooms`.`id`
			AND (`{$prefix}hr_reservations`.`check_in` < NOW())
			AND (`{$prefix}hr_reservations`.`check_out`> NOW()))
			{$extras}
		");
		while($row = \DB::Fetch_Array($sql)){
			$rooms[] = [
				'number'=> $row['number'],
				'id' 	=> $row['id'],
				'type' 	=> $row['type'],
				"url"	=> \URL::name("rubixcode/hotel/admin","rooms-detail",[
					"id"	=>	$row["id"]
				]) ,
			];
		}
		return $rooms;
	}

	public static function get_all($param = []){
		$available = self::get_available($param);
		$unavailable = self::get_unavailable($param);

		return array_merge($available,$unavailable);
	}

	public static function get_categories(){
		$category = [];
		$sql = \DB::_()->select("hr_room_type","*");
		foreach ($sql as $row) {
			$category[$row["id"]] = [
				"id"	=>	$row["id"],
				"name"	=>	$row["name"],
				'url' => [
					"detail"	=>	\URL::name("rubixcode/hotel/admin","category-detail",[ "id"	=>	$row["id"] ]),
					"edit"		=>	\URL::name("rubixcode/hotel/admin","category-edit",[ "id"	=>	$row["id"] ]),
					"rooms-by-category"	=>	\URL::name("rubixcode/hotel/admin","rooms-by-category",[ "id"	=>	$row["id"] ]),
				],
			];
		}
		return $category;
	}

	public static function add_room_type($param){
		$missing = array_diff_key([
			"name"	=> false,
			"brief"	=> false,
			"description"	=> false,
		],$param);
		if($missing) return [
			"type"	=>	"error",
			"title"	=>	"Please enter the following details",
			"message" => implode(",",array_keys($missing))
		];

		if(\DB::_()->count("hr_room_type",["name" => $param["name"]])) return [
			"type"	=>	"error",
			"title"	=>	"Room Type \"{$param["name"]}\" already exists!",
			"message"=>	"please choose a different name or "."<a href='".\URL::name("rubixcode/hotel/admin","category-edit",["edit"	=>	"name" , "name" => $param["name"]])."'>Click here</a> to edit {$param["name"]}"
		];

		\DB::_()->insert("hr_room_type",[
			"name"	=>	$param["name"],
			"brief"	=>	$param["brief"],
			"description"=>	$param["description"],
		]);

		return [
			"type"	=>	"success",
			"title"	=>	"Room type \"{$param["name"]}\" added Successfully!",
			"message"=>	"<a href='".\URL::name("rubixcode/hotel/admin","category-detail",["id" => \DB::_()->id()])."'>Click here</a> to view"
		];
	}

	public static function edit_room_type($param){
		$missing = array_diff_key([
			"id"	=> false,
			"name"	=> false,
			"brief"	=> false,
			"description"	=> false,
		],$param);
		if($missing) return [
			"type"	=>	"error",
			"title"	=>	"Please enter the following details",
			"message" => implode(",",array_keys($missing))
		];
		$res = \DB::_()->select("hr_room_type","*",[
			"id"	=>	$param["id"]
		]);
		if($res){
			$res = $res[0];
			if(\DB::_()->count("hr_room_type",["name" => $param["name"] , "id[!]" => $param["id"] ])) return [
				"type"	=>	"error",
				"title"	=>	"Room Type \"{$param["name"]}\" already exists!",
				"message"=>	"please choose a different name or "."<a href='".\URL::name("rubixcode/hotel/admin","category-edit",["edit"	=>	"name" , "name" => $param["name"]])."'>Click here</a> to edit {$param["name"]}"
			];
			\DB::_()->update("hr_room_type",[
				"name"	=>	$param["name"],
				"brief"	=>	$param["brief"],
				"description"	=>	$param["description"],
			],[ "id" => $param["id"] ]);
			return [
				"type"	=>	"succes",
				"title"	=>	"\"{$param["name"]}\" now updated",
				"message"=> "<a href='".\URL::name("rubixcode/hotel/admin","category-detail",["id" => $param["id"]])."'>Click here</a> to view"
			];
		}
		else return [
			"type"	=>	"error",
			"title"	=>	"Room type could not be found.",
			"message"=>	"Please try again"
		];
	}

	public static function delete_room_type($param){
		if(!isset($param["id"]) || !\DB::_()->count("hr_room_type",["id" => $param["id"] ])) return [
			"type"	=>	"error",
			"title"	=>	"Room type could not be found.",
			"message"=>	"Please try again"
		];
		$res = \DB::_()->select("hr_room_type",["name"],["id"=>$param["id"]])[0];
		\DB::_()->delete("hr_room_type",[ "id"	=> $param["id"]	]);
		\DB::_()->update("hr_rooms",["type" => 0],["type" => $param["id"]]);
		return [
			"type"	=>	"succes",
			"title"	=>	"\"{$res["name"]}\" was Successfully <b>deleted</b>",
		];
	}

	public static function get_room_type_details($param){
		$res = \DB::_()->select("hr_room_type","*",["id" => $param["id"]]);
		if($res){
			$res = $res[0];
			$res["url"]	= [
				"edit"	=>	\URL::name("rubixcode/hotel/admin","category-edit",["id" => $param["id"]]),
				"detail"=>	\URL::name("rubixcode/hotel/admin","category-detail",["id" => $param["id"]])
			];
		}
		return $res;
	}

	public static function add_rooms($param){
		$missing = array_diff_key([
			"number"	=> false,
			"category"	=> false,
		],$param);
		if($missing) return [
			"type"	=>	"error",
			"title"	=>	"Please enter the following details",
			"message" => implode(",",array_keys($missing))
		];

		if(\DB::_()->count("hr_rooms",["number" => $param["number"]])) return [
			"type"	=>	"error",
			"title"	=>	"Room \"{$param["number"]}\" already exists!",
			"message"=>	"please choose a different number or "."<a href='".\URL::name("rubixcode/hotel/admin","rooms-edit",["edit"	=>	"number" , "number" => $param["number"]])."'>Click here</a> to edit Room {$param["number"]}"
		];

		if(!\DB::_()->count("hr_room_type",["id" => $param["category"]])) return [
			"type"	=>	"error",
			"title"	=>	"Invalid Room Type"
		];

		\DB::_()->insert("hr_rooms",[
			"number"	=>	$param["number"],
			"type"		=>	$param["category"],
		]);

		return [
			"type"	=>	"success",
			"title"	=>	"Room \"{$param["number"]}\" added Successfully!",
			"message"=>	"<a href='".\URL::name("rubixcode/hotel/admin","rooms-detail",[ "room"	=> "id" , "id" => \DB::_()->id()])."'>Click here</a> to view"
		];
	}

	public static function edit_rooms($param){
		$missing = array_diff_key([
			"id"	=> false,
			"number"=> false,
			"category"=> false,
		],$param);
		if($missing) return [
			"type"	=>	"error",
			"title"	=>	"Please enter the following details",
			"message" => implode(",",array_keys($missing))
		];
		$res = \DB::_()->select("hr_rooms","*",[
			"id"	=>	$param["id"]
		]);
		if($res){
			$res = $res[0];
			if(\DB::_()->count("hr_rooms",["number" => $param["number"] , "id[!]" => $param["id"] ])) return [
				"type"	=>	"error",
				"title"	=>	"Room \"{$param["number"]}\" already exists!",
				"message"=>	"please choose a different number or "."<a href='".\URL::name("rubixcode/hotel/admin","rooms-edit",["edit"	=>	"number" , "number" => $param["number"]])."'>Click here</a> to edit {$param["number"]}"
			];
			\DB::_()->update("hr_rooms",[
				"number"=>	$param["number"],
				"type"	=>	$param["category"],
			],[ "id" => $param["id"] ]);
			return [
				"type"	=>	"succes",
				"title"	=>	"Room \"{$param["number"]}\" now updated",
				"message"=> "<a href='".\URL::name("rubixcode/hotel/admin","rooms-detail",["id" => $param["id"]])."'>Click here</a> to view"
			];
		}
		else return [
			"type"	=>	"error",
			"title"	=>	"Room type could not be found.",
			"message"=>	"Please try again"
		];
	}

	public static function delete_rooms($param){
		if(!isset($param["id"]) || !\DB::_()->count("hr_rooms",["id" => $param["id"] ])) return [
			"type"	=>	"error",
			"title"	=>	"Room could not be found.",
			"message"=>	"Please try again"
		];
		$res = \DB::_()->select("hr_rooms",["number"],["id"=>$param["id"]])[0];
		\DB::_()->delete("hr_rooms",[ "id"	=> $param["id"]	]);
		return [
			"type"	=>	"succes",
			"title"	=>	"Room \"{$res["number"]}\" was Successfully <b>deleted</b>",
		];
	}

	public static function get_room_details($param){
		$res = \DB::_()->select("hr_rooms","*",["id" => $param["id"]]);
		if($res){
			$res = $res[0];
			$res["url"]	= [
				"edit"	=>	\URL::name("rubixcode/hotel/admin","rooms-edit",["id" => $param["id"]]),
				"detail"=>	\URL::name("rubixcode/hotel/admin","rooms-detail",["id" => $param["id"]])
			];
			$res["reservation"] = RESERVATION::get_all_of_room(["id" => $res["type"]]);
			$res["type"] = self::get_room_type_details(["id" => $res["type"]]);
		}
		return $res;
	}

	public static function statistics(){
		return [
			"total"	=> \DB::_()->count("hr_rooms","id"),
			"available" => count(self::get_available())
		];
	}
}


?>
