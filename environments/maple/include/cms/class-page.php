<?php

// TODO : ALOT

namespace Maple\Cms;

class PAGE{
	public static function add($param = []){
		if(!is_array($param)) return false;

		$missing = array_diff_key([
			"name"		=>	false,
			"content" 	=>	false
		],$param);

		if($missing){
			\MAPLE::DashMessage([
				@"title"	=>	"{$param["name"]} Page could not be added because insufficient parameter",
				"message"	=>	"missing : ".implode(",",$missing),
				"type"		=>	"error"
			]);
			return false;
		}

		$param = array_merge([
			"clean"	=>	false
		],$param);

		$exists = false;
		$x = \DB::_()->select("pages","*",[ "name"	=>	$param["name"]]);
		$y = false;
		foreach ($x as $value) $y = $value;
		if($y) $exists = true;
		if(!$param["clean"] && $exists ) $param["content"] = $y["Content"].$param["content"];
		if($exists)	\DB::_()->update("pages",["Content" => $param["content"]],[ "name"	=>	$param["name"]]);
		else		\DB::_()->insert("pages",[
			"name"	=>	$param["name"],
			"Content" => $param["content"]
		]);
	}

}

?>
