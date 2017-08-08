<?php
	namespace maple\cms;
	/**
	 *
	 */
	class Log{
		public static function emergency($message)	{}
		public static function alert($message)		{}
		public static function critical($message)	{}
		public static function error($message)		{}
		public static function warning($message)	{}
		public static function notice($message)		{}
		public static function info($message)		{}
		public static function debug($message,$param = []){}

		public static function start_timer($name,$description)	{}
		public static function stop_timer($name)					{}
		public static function timer($description,$param)		{}

	}
?>
