<?php
	namespace TDNE;
	class Game{
		protected static $user = false;

		public static function Initilize(){
			self::$user = \MAPLE::UserDetail("ID");
		}

		public static function points(){
			$user = self::$user;
			$table = \DB::Query("SELECT * FROM `m_td_status` WHERE `ID`='{$user}'");
			$res = \DB::Fetch_Array($table);
			return isset($res["points"])?json_decode($res["points"],true):[];
		}

		public static function acheivement(){
			$res = [];

			return $res;
		}

		public static function rank(){
			$res = "1";
			return $res;
		}

		public static function rank_list(){
			$res = [
				"1" => [
					"id" => "ID",
					"name"=> "NAME",
					"icon"=> "ICON",
				]
			];
			return $res;
		}

		public static function h_get_points(){
			$data = self::points();
			$data["type"]  = $data ? "success" : "error";
			echo json_encode($data,JSON_PRETTY_PRINT);
		}

		public static function h_heartbeat(){
			$data = [
				"points"	 =>	self::points(),
				"acheivement"=> self::acheivement(),
				"rank"=> self::rank(),
				"rank_list"=> self::rank_list(),
				"scanner"	=>	\SECURITY::has_access("tdne-scanner")
			];
			echo json_encode($data,JSON_PRETTY_PRINT);
		}

		public static function h_scanner_heartbeat(){
			$data = [
				"points"	 =>	[],
				"acheivement"=> false,
				"rank"=> 0,
				"rank_list"=> [],
				"scanner"	=>	\SECURITY::has_access("tdne-scanner")
			];
			echo json_encode($data,JSON_PRETTY_PRINT);
		}

		public static function h_scaned(){
			$user = [
				"name"	=>	"",
				"id"	=>	"",
				"icon"	=>	"",
				"email"	=>	"",
			];
			if(\URL::has_request(["data"])){
				$data = json_decode($_REQUEST["data"],true);
				$table = \DB::Query("SELECT * FROM `m_users` WHERE `Login`='{$data["username"]}'");
				$res = \DB::Fetch_Array($table);
				echo json_encode([
					"type"	=>	"succuss",
					"user"	=>	[
						"name"	=>	$res["Name"],
					],
				]);
			}
			else echo json_encode(["type" => "error"]);
		}

		public static function h_get_rank(){
		}

		public static function h_get_rank_list(){
		}

		public static function h_get_task_detail(){
		}

		public static function h_get_feeds(){
		}

		public static function h_get_blogs_list(){
		}

		public static function h_get_blog_details(){
		}

		public static function h_get_rewards_list(){
		}

		public static function h_get_reward_detail(){
		}

		public static function h_do_contacts_sync(){
		}

		public static function h_get_avatars_list(){
		}

		public static function h_get_avatar(){
		}

		public static function h_get_acheivements_list(){
		}

		public static function h_get_acheivement(){
		}

		public static function h_get_timeline(){
		}

	}

	GAME::Initilize();
?>
