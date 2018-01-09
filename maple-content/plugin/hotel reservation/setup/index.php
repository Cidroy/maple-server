<?php

class HRS{

	public static function Activate($param){
		$tabels = [
			"hr_clients"=>	[
				"ID"		=>	[ "primary"	=>	true, "type"		=>	"INT", "auto-increment"	=>	true, ],
				"Name"		=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"Phone"		=>	[ "type"	=>	"VARCHAR", "length"	=>	20 ],
				"Email"		=>	[ "type"	=>	"VARCHAR", "length"	=>	100 ],
				"Address"	=>	[ "type"	=>	"TEXT" ],
				"City"		=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"State"		=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"Zip"		=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"Passport"	=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"Proof"		=>	[ "type"	=>	"TEXT" ],
			],
			"hr_rates"	=>	[
				"ID"		=>	[ "primary"	=>	true, "type"		=>	"INT", "auto-increment"	=>	true, ],
				"Type"		=>	[ "type"	=>	"INT" ],
				"Rate"		=>	[ "type"	=>	"FLOAT" ],
				"Starts"	=>	[ "type"	=>	"DATE" ],
				"Ends"		=>	[ "type"	=>	"DATE" ],
				"Comment"	=>	[ "type"	=>	"VARCHAR", "length"	=> 300 ],
			],
			"hr_reservations"	=>	[
				"ID"		=>	[ "type"	=>	"INT", "primary"	=>	true,  "auto-increment"	=>	true, ],
				"Room"		=>	[ "type"	=>	"INT" ],
				"Client"	=>	[ "type"	=>	"INT" ],
				"C_IN"		=>	[ "type"	=>	"DATE" ],
				"C_OUT"		=>	[ "type"	=>	"DATE" ],
				"Payment"	=>	[ "type"	=>	"VARCHAR", "length"	=>	50 ],
				"Comment"	=>	[ "type"	=>	"TEXT" ],
				"Complete"	=>	[ "type"	=>	"INT", "length"	=>	11 ],
			],
			"hr_rooms"	=>	[
				"ID"		=>	[ "type"	=>	"INT", "primary"	=>	true,  "auto-increment"	=>	true, ],
				"Type"		=>	[ "type"	=>	"INT" ],
				"Number"	=>	[ "type"	=>	"VARCHAR", "length"	=>	10 ],
			],
			"hr_room_type"	=>	[
				"ID"		=>	[ "type"	=>	"INT", "primary"	=>	true,  "auto-increment"	=>	true, ],
				"Name"		=>	[ "type"	=>	"VARCHAR", "length"	=>	120 ],
				"Brief"		=>	[ "type"	=>	"VARCHAR", "length"	=>	120 ],
				"Description"=>	[ "type"	=>	"TEXT" ],
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
				"hr_room_type"
			]
		];
	}

}

?>
