<?php
	namespace Maple;

	class QR{
		public static function show(){
			echo \TEMPLATE::Render("maple/docs","barcode/docs",[]);
		}

		public static function generate($param = []){
			$param = array_merge([
				"overlay"	=>	false,
				"overlay_size" =>	"128",
				"size"		=>	"1024",
				"content"	=>	false,
			],$param);

			// TODO : Generate QR

			return [
				"extention"	=>	"png",
				"encoding"	=>	"base64",
				"data"		=>	null
			];
		}

		public static function decode($param){
			$param = array_merge([
				"extention"	=>	"png",
				"encoding"	=>	"base64",
				"data"		=>	null
			],$param);

			// TODO : decode QR

			return null;
		}
	}
?>
