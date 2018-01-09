<?php
	namespace maple\cms;
	/**
	 *
	 */
	class Log{
		public static function emergency($message){
			ERROR::Log($message,"emergency");
		}
		public static function alert($message){
			ERROR::Log($message,"alert");
		}
		public static function critical($message){
			ERROR::Log($message,"critical");
		}
		public static function error($message){
			ERROR::Log($message,"error");
		}
		public static function warning($message){
			ERROR::Log($message,"warning");
		}
		public static function notice($message){
			ERROR::Log($message,"notice");
		}
		public static function info($message){
			ERROR::Log($message,"info");
		}
		public static function debug($message,$param = []){
			if($param) ERROR::Log([
				"message" => $message,
				"parameter"=>$param
			],"debug");
			else ERROR::Log($message,"debug");
		}

		public static function start_timer($name,$description){ ERROR::StartTimer($name,$description); }
		public static function stop_timer($name){ ERROR::StopTimer($name); }
		public static function timer($description,$param){ ERROR::Timer($description,$param); }

	}
?>
