<?php

namespace TDNE;

class Setup{

	public static function Activate(){
		$tables = [
			"td_completitions"	=>	[
				"id"	=>	["type" => 	"INT" , "primary"	=>	true,  "auto-increment"	=>	true, ],
				"user"	=>	["type" =>	"INT"],
				"task"	=>	["type" =>	"INT"],
				"hub"	=>	["type" =>	"INT"],
				"time"	=>	["type" =>	"DATETIME"],
				"gained"=>	["type" =>	"VARCHAR" , "length" => 300],
			],
			"td_hub_a"	=>	[
				"id"	=>	["type" => 	"INT" , "primary"	=>	true,  "auto-increment"	=>	true, ],
				"location"	=>	["type" =>	"INT"],
			],
			"td_locations"	=>	[
				"id"	=>	["type" => 	"INT" , "primary"	=>	true,  "auto-increment"	=>	true, ],
				"name"	=>	["type" =>	"VARCHAR" , "length" => 100],
				"description"=>	["type" =>	"VARCHAR" , "length" => 500],
				"address"=>	["type" =>	"VARCHAR" , "length" => 500],
				"latitude"=>["type" =>	"VARCHAR" , "length" => 50],
				"longitude"=>["type" =>	"VARCHAR" , "length" => 50],
				"altitude"=>["type" =>	"VARCHAR" , "length" => 50],
				"safe"	=>	["type" =>	"INT"],
				"type"	=>	["type" =>	"INT"],
			],
			"td_locations_type"	=>	[
				"id"	=>	["type" => 	"INT" , "primary"	=>	true,  "auto-increment"	=>	true, ],
				"name"	=>	["type" =>	"VARCHAR" , "length" => 20],
			],
			"td_points"	=>	[
				"id"	=>	["type" => 	"INT" , "primary"	=>	true,  "auto-increment"	=>	true, ],
				"name"	=>	["type" =>	"VARCHAR" , "length" => 30],
				"value"	=>	["type" =>	"DECIMAL" , "length" => "10,0"],
			],
			"td_safe"	=>	[
				"id"	=>	["type" => 	"INT" , "primary"	=>	true,  "auto-increment"	=>	true, ],
				"name"	=>	["type" =>	"VARCHAR" , "length" => 30],
				"description"	=>	["type" =>	"VARCHAR" , "length" => 300],
			],
			"td_status"	=>	[
				"id"	=>	["type" => 	"INT" , "primary"	=>	true,  "auto-increment"	=>	true, ],
				"active"=>	["type" =>	"TINYINT" , "length" => 1],
				"points"=>	["type" =>	"VARCHAR" , "length" => 300],
			],
			"td_tasks"	=>	[
				"id"	=>	["type" => 	"INT" , "primary"	=>	true,  "auto-increment"	=>	true, ],
				"active"=>	["type" =>	"INT" ],
				"name"	=>	["type" =>	"VARCHAR" , "length" => 50],
				"description"=>	["type" =>	"TEXT" ],
				"safe"	=>	["type" =>	"INT" ],
				"points"=>	["type" =>	"VARCHAR" , "length" => 150],
				"photos"=>	["type" =>	"TEXT" ],
				"difficulty"=>	["type" =>	"DECIMAL" , "length" => "10,0"],
				"nearby"=>	["type" =>	"VARCHAR" , "length" => 300],
				"location"	=>	["type" =>	"INT" ],
				"routes"=>	["type" =>	"TEXT" ],
				"created"=>	["type" =>	"DATETIME" ],
			],
		];

		return [
			"Tables"	=>	$tables,
		];
	}

	public static function Deactivate(){
		return [
			"tables"	=>	[
				"td_completitions",
				"td_hub_a",
				"td_locations",
				"td_locations_type",
				"td_points",
				"td_safe",
				"td_status",
				"td_tasks",
			]
		];
	}

}


?>
