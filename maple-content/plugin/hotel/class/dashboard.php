<?php
namespace rubixcode\hotel;

class DASHBOARD{

	public static function d_total_income(){ echo \TEMPLATE::Render("rubixcode/hotel","dashboard/total-income",PAYMENT::statistics()); }

	public static function d_total_clients(){ echo \TEMPLATE::Render("rubixcode/hotel","dashboard/total-client",[ "clients" => CLIENT::statistics()]); }

	public static function d_room_stats(){
		echo \TEMPLATE::Render("rubixcode/hotel","dashboard/room-stats",[
			"rooms"	=>	ROOM::statistics(),
		]);
	}

	public static function d_season_stats(){
		echo \TEMPLATE::Render("rubixcode/hotel","dashboard/season-stats",[
			"seasons"	=> RATES::seasons(),
		]);
	}

}
?>
