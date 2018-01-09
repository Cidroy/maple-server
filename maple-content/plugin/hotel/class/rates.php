<?php
namespace rubixcode\hotel;

class RATES{
	public static function get_by_room($param){
		$param["type"] = ROOM::get_room_details(["id" => $param["id"]])["type"]["id"];
		$param["check_out"] = $param["check_out"]?$param["check_out"]:$param["check_in"];
		$days =	\TIME::parse($param["check_in"])->diffInDays(\TIME::parse($param["check_out"]));
		$days = $days?$days:1;
		$res = \DB::_()->max("hr_rates","rate",[
			"type"	=>	$param["type"],
			"starts[<=]"=>	$param["check_in"],
			"ends[>]"=>	$param["check_in"],
		]);
		$res = $res?$res:0;
		return $res*$days;
	}

	public static function seasons(){
		$res = \DB::_()->select("hr_rates","*");
		$output = [];
		foreach ($res as $row) {
			if(!isset($output["{$row["starts"]}-{$row["ends"]}"]))
				$output["{$row["starts"]}-{$row["ends"]}"] = [
					"starts"	=>	$row["starts"],
					"ends"		=>	$row["ends"],
					"rates"		=>	[],
					"url"		=>	[
						"edit"	=>	\URL::name("rubixcode/hotel/admin","rates-edit",["begin" => $row["starts"]]),
						"delete"=>	\URL::name("rubixcode/hotel/admin","rates-delete",["begin" => $row["starts"]]),
					]
				];
			$output["{$row["starts"]}-{$row["ends"]}"]["rates"][$row["type"]] = $row["rate"];
		}
		return $output;
	}

	public static function season_detail($param){
		$res = \DB::_()->select("hr_rates",[
			"[>]hr_room_type"	=>	[ "type" => "id"	]
		],"*",[
			"starts"	=>	$param["starts"]
		]);
		$output = [];
		foreach ($res as $row) {
			if(!isset($output["{$row["starts"]}-{$row["ends"]}"]))
				$output["{$row["starts"]}-{$row["ends"]}"] = [
					"starts"	=>	$row["starts"],
					"ends"		=>	$row["ends"],
					"rates"		=>	[],
					"url"		=>	[
						"edit"	=>	\URL::name("rubixcode/hotel/admin","rates-edit",["begin" => $row["starts"]]),
						"delete"=>	\URL::name("rubixcode/hotel/admin","rates-delete",["begin" => $row["starts"]]),
					]
				];
			$output["{$row["starts"]}-{$row["ends"]}"]["rates"][$row["type"]] = [
				"id"	=>	$row["type"],
				"name"	=>	$row["name"],
				"rate"	=>	$row["rate"],
			];
		}
		foreach ($output as $value) {
			return $value;
		}
		return false;
	}

	public static function season_add($param){
		$insert = [];
		$param["starts"] = \TIME::parse($param["starts"])->toDateString();
		$param["ends"] = \TIME::parse($param["ends"])->toDateString();
		$res = \DB::_()->select("hr_rates","*",[
			"OR"	=>	[
				"AND #1"	=>	[
					"starts[<=]"	=>	$param["starts"],
					"ends[>]"		=>	$param["starts"],
				],
				"AND #2"	=>	[
					"starts[<=]"	=>	$param["ends"],
					"ends[>]"		=>	$param["ends"],
				],
			]
		]);
		if($res) return [
			"type"	=>	"error",
			"title" => "There is Already an Existing Season"
		];
		foreach ($param["type"] as $key => $value) {
			$insert[] = [
				"type"	=>	$key,
				"rate"	=>	$value,
				"starts"=>	$param["starts"],
				"ends"	=>	$param["ends"],
				"comment"=>	"",
			];
		}
		$insert = \DB::_()->insert("hr_rates",$insert);
		return [
			"type"	=>	"success",
			"title"	=>	"Season Successfully Added!",
		];
	}

	public static function season_edit($param){
		$res = \DB::_()->select("hr_rates","*",[ "starts"	=>	$param["starts"] ]);
		if(!$res) return [
			"type"	=>	"error",
			"title"	=>	"Invalid Season Selected"
		];
		foreach ($param["type"] as $type => $rate) {
			\DB::_()->update("hr_rates",[ "rate"	=>	$rate ],[
				"type"	=>	$type,
				"starts"=>	$param["starts"]
			]);
		}
		return [
			"type"	=>	"success",
			"title"	=>	"Season Edited Successfully!"
		];
	}

	public static function season_delete($param){
		$res = \DB::_()->select("hr_rates","*",[ "starts"	=>	$param["starts"] ]);
		if(!$res) return [
			"type"	=>	"error",
			"title"	=>	"Invalid Season Selected"
		];
		\DB::_()->delete("hr_rates",[ "starts"	=>	$param["starts"] ]);
		return [
			"type"	=>	"success",
			"title"	=>	"Season Edited Successfully!"
		];
	}
}

?>
