<?php
namespace rubixcode\hotel;

class RESERVATION{

	public static function get_all_of_room($param){
		if(!isset($param["id"])) return [
			"type"	=>	"error",
			"title"	=>	"insufficient parameters"
		];

		$current = self::room_reserations(array_merge([
			"check_in"	=>	["logic"		=>	"<=","time"		=>	"NOW()"],
			"check_out"	=>	["logic"		=>	">","time"		=>	"NOW()"],
			"complete" => false
		],$param));
		$current = $current ? $current[0] : [];
		$status  = self::room_status($param);
		$reservation = [
			"status"	=>	$status,
			"reservations"	=>	[
				"current"	=>	$current,
				"history"		=>	array_merge(
					self::room_reserations(array_merge([
						"check_out"	=>	["logic"		=>	"<","time"		=>	"NOW()"],
					],$param)),
					self::room_reserations(array_merge([
						"check_in"	=>	["logic"		=>	"<=","time"		=>	"NOW()"],
						"check_out"	=>	["logic"		=>	">","time"		=>	"NOW()"],
						"complete" => true
					],$param))
				),
				"future"		=>	self::room_reserations(array_merge([
					"check_in"	=>	["logic"		=>	">","time"		=>	"NOW()"]
				],$param)),
			],
			"url"	=>	[
				"book"	=>	!$status?\URL::name("rubixcode/hotel/admin","rooms-reserve",["id" => $param["id"]]):false,
				"checkout"	=>	$status?\URL::name("rubixcode/hotel/admin","reservation-checkout",["id" => $param["id"]]):false,
			]
		];

		return $reservation;
	}

	public static function room_reserations($param){
		if(!isset($param["id"])) return false;
		$sql_param = [
			"room"	=>	$param["id"],
			"ORDER"	=>	[
				"check_in"	=>	"ASC"
			]
		];
		$room_bookings = [];
		if(isset($param["check_in"])) $sql_param["#check_in[{$param["check_in"]["logic"]}]"] = $param["check_in"]["time"];
		if(isset($param["check_out"])) $sql_param["#check_out[{$param["check_out"]["logic"]}]"] = $param["check_out"]["time"];
		if(isset($param["complete"])) $sql_param["complete"] = $param["complete"];
		$sql = \DB::_()->select("hr_reservations","*",$sql_param);
		foreach ($sql as $row){
			$room_bookings[] = [
				'client' => CLIENT::get_details(["id"	=>	$row["client"]]),
				'booking'=> [
					'start'	=> $row['check_in'],
					'end'	=> $row['check_out'],
				],
			];
		}
		return $room_bookings;
	}

	public static function room_status($param){
		if(!isset($param["id"])) return false;
		return \DB::_()->count("hr_reservations",[
			"room"	=>	$param["id"],
			"#check_in[<=]"	=>	"NOW()",
			"#check_out[>]"	=>	"NOW()",
			"complete[!]"	=>	false
		])?true : false;
	}

	public static function room_status_on($param){
		if(!isset($param["id"])) return false;
		return \DB::_()->count("hr_reservations",[
			"room"	=>	$param["id"],
			"#check_in[<=]"	=>	"STR_TO_DATE('{$param["check_in"]}','%d %M %Y')",
			"#check_out[>]"	=>	"STR_TO_DATE('{$param["check_out"]}','%d %M %Y')",
			"complete"	=>	false
		])?true : false;
	}

	public static function reserve_room($param){
		# TODO : check missing
		$status = self::room_status_on([
			"id"		=>	$param["room"],
			"check_in"	=>	$param["check_in"],
			"check_out"	=>	$param["check_out"],
		]);
		if($status) return ["type"	=>	"error","title"	=>	"The Room is not available for the dates",];
		if(! CLIENT::exists(["id" => $param["client"]])) return ["type"	=>	"error","title"	=>	"Client Does not exists"];
		$from = new \TIME($param["check_in"]);
		$to   = new \TIME($param["check_out"]);
		$yest = \TIME::now();
		$yest = $yest->addDays(-1);
		if($yest->gte($from) || $from->gte($to)) return [
			"type"	=>	"error",
			"title"	=>	"Invalid Duration"
		];
		if(
			\DB::_()->insert("hr_reservations",[
				"room"		=>	$param["room"],
				"client"	=>	$param["client"],
				"check_in"	=>	$param["check_in"],
				"check_out"	=>	$param["check_out"],
				"payment"	=>	$param["payment"],
				"comment"	=>	$param["comment"],
				"complete"	=>	0
			])
		) return [
			"type"	=>	"success",
			"title"	=>	"Room Reserved Successfully",
			"message"=>	"<a href='".\URL::name("rubixcode/hotel/admin","reservation-detail",["id" => \DB::_()->id()])."'>Click Here</a> to view"
		];
		else
		return [
			"type"	=>	"error",
			"title"	=>	"an unknown error has occured"
		];
	}

	public static function get_timeline($logic = ">="){
		$prefix = \DB::_()->prefix;
		$ret = [];
		$dates = \DB::Query("SELECT DISTINCT `check_in` FROM `{$prefix}hr_reservations` WHERE `check_out` {$logic} NOW()");
		while ($row = \DB::Fetch_Array($dates)) {
			$ret[$row["check_in"]] = \DB::_()->select("hr_reservations",[
				"[>]hr_rooms"	=>	["room"	=>	"id"],
				"[>]hr_room_type"=>	["hr_rooms.id"	=>	"id"],
				"[>]users"		=>	["client"	=>	"ID"]
			],
			[
				"hr_reservations.id",
				"hr_reservations.check_in",
				"hr_reservations.check_out",
				"hr_reservations.complete",
				"room"	=>	[
					"hr_rooms.id(room-id)",
					"hr_rooms.number",
					"type"	=>	[
						"hr_room_type.id(type-id)",
						"hr_room_type.name(type-name)",
						"hr_room_type.brief",
						"hr_room_type.description",
					]
				],
				"client"=> [
					"users.ID(client-id)",
					"users.Login(login)",
					"users.Name(client-name)",
					"users.Email(email)",
					"users.Gender(gender)",
					"users.Age(age)",
					"users.Phone(phone)",
					"users.Address(address)",
				]
			]
			,[
				"check_in"	=>	$row["check_in"],
				"ORDER"		=>	["hr_reservations.check_in" => "ASC"]
			]);
		}

		$ret = array_reverse($ret);

		return [
			"timeline"	=>	$ret,
			"url"	=>	[
				"room"			=>	[ "detail"	=>	\URL::name("rubixcode/hotel/admin","rooms-detail",["id"	=>	""])],
				"client"		=>	[ "detail"	=>	\URL::name("rubixcode/hotel/admin","client-detail",["id"	=>	""])],
				"reservation"	=>	[ "detail"	=>	\URL::name("rubixcode/hotel/admin","reservation-detail",["id"	=>	""])],
				"category"		=>	[ "detail"	=>	\URL::name("rubixcode/hotel/admin","category-detail",["id"	=>	""])],
			]
		];
	}

	public static function details($param){
		if(!isset($param["id"])) return false;
		$res = \DB::_()->select("hr_reservations","*",["id"	=>	$param["id"]]);
		if(count($res)){
			$res = $res[0];
			$res["room"]	= ROOM::get_room_details(["id" => $res["room"]]);
			$res["client"]	= CLIENT::get_details(["id" => $res["client"]]);
			$res["payment"]	= PAYMENT::details(["id" => $res["payment"]]);
			$res["status"] = "unknown";
			$now = \TIME::now();
			$check_in = new \TIME($res["check_in"]);
			$check_out = new \TIME($res["check_out"]);
			if( $check_out->lte($now) ) $res["status"] = "over";
			else if( $check_in->lte($now) && $now->lte($check_out) ) $res["status"] = "running";
			else if( $check_in->gt($now) ) $res["status"] = "upcoming";
			switch ($res["status"]){
				case 'over':
					$res["url"]	= [];
					break;
				case 'running':
					$res["url"]	= [
						"cancel"	=>	\URL::name("rubixcode/hotel/admin","reservation-cancel",["id"	=>	$res["id"]]),
						"checkout"	=>	\URL::name("rubixcode/hotel/admin","reservation-checkout",["id"	=>	$res["id"]]),
					];
					break;
				case 'upcoming':
					$res["url"]	= [
						"cancel"	=>	\URL::name("rubixcode/hotel/admin","reservation-cancel",["id"	=>	$res["id"]]),
					];
					break;
			}
			return $res;
		} else return false;
	}

	public static function checkout($param){
		$res = self::details($param);
		if(!isset($param["id"]) || !$res ) return [
			"type"	=>	"error",
			"title"	=>	"insufficient parameters"
		];
		if($res["complete"] == '0'){
			if($res["status"] == "upcoming") return [
				"type"	=>	"error",
				"title"	=>	"Checkout Failed Because Reservation is Still Pending",
				"message"	=>	"The Reservation for <a href='{$res["room"]["url"]["detail"]}'>Room {$res["room"]["number"]}</a> by Client : <a href='{$res["client"]["url"]["detail"]}'>{$res["client"]["name"]}</a> is due to happen. <br> <a href='{$res['url']['cancel']}'>Did you want to cancel ?</a>"
			];
			\DB::_()->update("hr_reservations",["complete" => 1],["id" => $param["id"]]);
			return [
				"type"	=>	"success",
				"title"	=>	"Checked Out",
				"message"	=>	"The Reservation for <a href='{$res["room"]["url"]["detail"]}'>Room {$res["room"]["number"]}</a> by Client : <a href='{$res["client"]["url"]["detail"]}'>{$res["client"]["name"]}</a> is checked out."
			];
		}
		else if ($res["complete"] == "-1") return [
			"type"	=>	"error",
			"title"	=>	"Checkout Failed Because Reservation was Previously Cancelled!",
			"message"	=>	"The Reservation for <a href='{$res["room"]["url"]["detail"]}'>Room {$res["room"]["number"]}</a> by Client : <a href='{$res["client"]["url"]["detail"]}'>{$res["client"]["name"]}</a> was already cancelled."
		];
		else if ($res["complete"] == "1") return [
			"type"	=>	"warning",
			"title"	=>	"Can Not Check Out Twice",
			"message"	=>	"The Reservation for <a href='{$res["room"]["url"]["detail"]}'>Room {$res["room"]["number"]}</a> by Client : <a href='{$res["client"]["url"]["detail"]}'>{$res["client"]["name"]}</a> was already Checked Out."
		];
	}

	public static function cancel($param){
		$res = self::details($param);
		if(!isset($param["id"]) || !$res ) return [
			"type"	=>	"error",
			"title"	=>	"insufficient parameters"
		];
		if($res["complete"] == '0'){
			\DB::_()->update("hr_reservations",["complete" => "-1"],["id" => $param["id"]]);
			return [
				"type"	=>	"success",
				"title"	=>	"Reservation Cancelled!",
				"message"	=>	"The Reservation for <a href='{$res["room"]["url"]["detail"]}'>Room {$res["room"]["number"]}</a> by Client : <a href='{$res["client"]["url"]["detail"]}'>{$res["client"]["name"]}</a> is Cancelled."
			];
		}
		else if ($res["complete"] == "-1") return [
			"type"	=>	"warning",
			"title"	=>	"Cancellation Failed Because Reservation already Previously Cancelled!",
			"message"	=>	"The Reservation for <a href='{$res["room"]["url"]["detail"]}'>Room {$res["room"]["number"]}</a> by Client : <a href='{$res["client"]["url"]["detail"]}'>{$res["client"]["name"]}</a> was already cancelled."
		];
		else if ($res["complete"] == "1") return [
			"type"	=>	"error",
			"title"	=>	"Cancellation Failed!",
			"message"	=>	"The Reservation for <a href='{$res["room"]["url"]["detail"]}'>Room {$res["room"]["number"]}</a> by Client : <a href='{$res["client"]["url"]["detail"]}'>{$res["client"]["name"]}</a> was not cancelled because Client was already Checked Out."
		];
	}

	public static function by_client($param){
		if(!isset($param["id"]))	return false;
		$ret = [];
		$ret = \DB::_()->select("hr_reservations","*",["client" => $param["id"]]);
		foreach ($ret as $key => $value) {
			$ret[$key]["payment"] = PAYMENT::details(["id"	=>	$ret[$key]["payment"]]);
			$ret[$key]["room"] = ROOM::get_room_details(["id"	=>	$ret[$key]["room"]]);
			$ret[$key]["status"] = "unknown";
			$now = \TIME::now();
			$check_in = new \TIME($ret[$key]["check_in"]);
			$check_out = new \TIME($ret[$key]["check_out"]);
			if( $check_out->lte($now) ) $ret[$key]["status"] = "over";
			else if( $check_in->lte($now) && $now->lte($check_out) ) $ret[$key]["status"] = "running";
			else if( $check_in->gt($now) ) $ret[$key]["status"] = "upcoming";
			switch ($ret[$key]["status"]){
				case 'over':
					$ret[$key]["url"]	= [
						"detail"	=>	\URL::name("rubixcode/hotel/admin","reservation-detail",["id" => $ret[$key]["id"]]),
					];
					break;
				case 'running':
					$ret[$key]["url"]	= [
						"detail"	=>	\URL::name("rubixcode/hotel/admin","reservation-detail",["id" => $ret[$key]["id"]]),
						"cancel"	=>	\URL::name("rubixcode/hotel/admin","reservation-cancel",["id"	=>	$ret[$key]["id"]]),
						"checkout"	=>	\URL::name("rubixcode/hotel/admin","reservation-checkout",["id"	=>	$ret[$key]["id"]]),
					];
					break;
				case 'upcoming':
					$ret[$key]["url"]	= [
						"detail"	=>	\URL::name("rubixcode/hotel/admin","reservation-detail",["id" => $ret[$key]["id"]]),
						"cancel"	=>	\URL::name("rubixcode/hotel/admin","reservation-cancel",["id"	=>	$ret[$key]["id"]]),
					];
					break;
			}
		}
		return $ret;
	}


}

?>
