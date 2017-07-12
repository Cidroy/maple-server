<?php

namespace rubixcode\hotel;

class SETUP{

	public static function Activate($param){
		$tabels = [
			"hr_clients"=>	[
				"id"		=>	[ "primary"	=>	true, "type"		=>	"INT", "auto-increment"	=>	true, ],
				"city"		=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"state"		=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"zip"		=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"passport"	=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"proof"		=>	[ "type"	=>	"TEXT" ],
			],
			"hr_rates"	=>	[
				"id"		=>	[ "primary"	=>	true, "type"		=>	"INT", "auto-increment"	=>	true, ],
				"type"		=>	[ "type"	=>	"INT" ],
				"rate"		=>	[ "type"	=>	"FLOAT" ],
				"starts"	=>	[ "type"	=>	"DATE" ],
				"ends"		=>	[ "type"	=>	"DATE" ],
				"comment"	=>	[ "type"	=>	"VARCHAR", "length"	=> 300 ],
			],
			"hr_reservations"	=>	[
				"id"		=>	[ "type"	=>	"INT", "primary"	=>	true,  "auto-increment"	=>	true, ],
				"room"		=>	[ "type"	=>	"INT" ],
				"client"	=>	[ "type"	=>	"INT" ],
				"check_in"	=>	[ "type"	=>	"DATE" ],
				"check_out"	=>	[ "type"	=>	"DATE" ],
				"payment"	=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"comment"	=>	[ "type"	=>	"TEXT" ],
				"complete"	=>	[ "type"	=>	"INT", "length"	=>	11 ],
			],
			"hr_rooms"	=>	[
				"id"		=>	[ "type"	=>	"INT", "primary"	=>	true,  "auto-increment"	=>	true, ],
				"type"		=>	[ "type"	=>	"INT" ],
				"number"	=>	[ "type"	=>	"VARCHAR", "length"	=>	10 ],
			],
			"hr_room_type"	=>	[
				"id"		=>	[ "type"	=>	"INT", "primary"	=>	true,  "auto-increment"	=>	true, ],
				"name"		=>	[ "type"	=>	"VARCHAR", "length"	=>	120 ],
				"brief"		=>	[ "type"	=>	"VARCHAR", "length"	=>	120 ],
				"description"=>	[ "type"	=>	"TEXT" ],
			],
			"hr_payments"	=>	[
				"id"		=>	[ "type"	=>	"INT", "primary"	=>	true,  "auto-increment"	=>	true, ],
				"amount"	=>	[ "type"	=>	"DECIMAL" ],
				"time"		=>	[ "type"	=>	"TIMESTAMP" ],
				"type"		=>	[ "type"	=>	"VARCHAR", "length"	=>	30 ],
				"meta"		=>	[ "type"	=>	"VARCHAR", "length"	=>	300 ],
			]
		];

		return [
			"Tables"	=>	$tabels,
		];
	}

	public static function Deactivate($param){
		return [
			"tables"	=>	[
				"hr_rates",
				"hr_clients",
				"hr_reservations",
				"hr_rooms",
				"hr_room_type",
				"hr_payments"
			]
		];
	}

}

?>
