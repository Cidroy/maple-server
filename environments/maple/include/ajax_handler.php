<?php
/*
add plugin default namespace
add specific detils for each ajax
	- namespace
*/


$ajax_respose_type='';
function ajax_buffer($buffer){
	isset($_REQUEST['ajax_respose_type'])?(isset($ajax_respose_type)?header("Content-Type:$_REQUEST[ajax_respose_type]"):header("Content-Type:$ajax_respose_type")):false;
	return $buffer;
}

$core_process = array('heartbeat');
if(!isset($_SERVER['HTTP_ORIGIN'])) $_SERVER['HTTP_ORIGIN'] = '*';
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Origin: {$_SERVER["HTTP_ORIGIN"]}");
header("Access-Control-Allow-Methods: GET,POST,PUT");
header("Access-Control-Allow-Headers: Content-Type, *");
$ajax_function = [];

$cached = CACHE::get("maple","active-ajax",false,["user-specific"=>true]);
if(!is_array($cached)){
	foreach (FILE::get_folders(ROOT.PLG) as $k){
		$json=ROOT.PLG."$k/package.json";
		if(file_exists($json)){
			$json = json_decode(file_get_contents($json),true);
			$json = array_merge([
				"Maple"	=>	[
					"Active"	=>	false,
				],
				"AJAX"	=>	[],
			],$json);
			if(!$json["Maple"]["Active"]) continue;
			$ajax = $json['AJAX'];
			foreach($ajax as $a => $fun)
				if(is_array($fun)){
					if(isset($fun['permission']) && !SECURITY::has_access($fun['permission'])) continue;
					$ajax_function[$a] = array_merge([
						"mime"	=>	"default",
						"method"=>	["GET","PUT","POST","DELETE"],
						"extention"	=>	"default",
						"cache"	=>	"default"
					],$fun);

				}
				else $ajax_function[$a] = [
					"function"	=>	$fun,
					"method"=>	["GET","PUT","POST","DELETE"],
					"mime"	=>	"default",
					"extention"	=>	"default",
					"cache"	=>	"default"
				];
		}
	}
	CACHE::put("maple","active-ajax",$ajax_function,["user-specific"=>true]);
} else {
	$ajax_function = $cached;
}

ob_start("ajax_buffer");
	if(isset($ajax_function[$_REQUEST['maple_ajax_action']])){
		try{
			if(!in_array($_SERVER['REQUEST_METHOD'], $ajax_function[$_REQUEST['maple_ajax_action']]['method'] ))
				throw new Exception("Error Processing Request", 1);

			if($ajax_function[$_REQUEST['maple_ajax_action']]["extention"]!="default") header("Content-Type: ".FILE::mime_by_extention($ajax_function[$_REQUEST['maple_ajax_action']]['extention']),true );
			if($ajax_function[$_REQUEST['maple_ajax_action']]["cache"]!="default") header("Cache-Control: {$ajax_function[$_REQUEST['maple_ajax_action']]['cache']}");
			if($ajax_function[$_REQUEST['maple_ajax_action']]["mime"]!="default") header("Content-Type: {$ajax_function[$_REQUEST['maple_ajax_action']]['mime']}");
			echo call_user_func( $ajax_function[$_REQUEST['maple_ajax_action']]["function"] );
		}
		catch(Exception $e){
			header("Content-Type:application/json");
			$ret = array('type' => 'error', 'do'=>'notify',
				'notify' => array('caption' => 'Oops! Something went wrong!',
								 'content' => 'An Internal error has occured, thats all we know. Please try again after some time. [404x03]',
								 'icon'		=> 'warning'),
				'exception'	=>	$e
			);
		 	Log::debug('Undifined AJAX action',array('maple_ajax_action'=>$_REQUEST['maple_ajax_action'],'function'=>$ajax_function[$_REQUEST['maple_ajax_action']]));
			echo json_encode($ret);
		}
	}
	else {
		$ret = array('type' => 'error', 'do'=>'notify',
				'notify' => array('caption' => 'Oops! Something went wrong!',
								 'content' => 'An Internal error has occured, thats all we know. Please try again after some time. [403x03]',
								 'icon'		=> 'warning')
			);
		Log::debug('Invalid AJAX Request',$_REQUEST);
		echo json_encode($ret);
	}

ob_end_flush();
?>
