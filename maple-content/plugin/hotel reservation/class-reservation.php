<?php
/**
*
*/
class HR{
	public static function shortcode($value){
		# code...
		# registration form
		# gallery
		switch ($value) {
			case 'registration-form':
				UI::content()->add('HR::registration_form','');
				break;
			case 'gallery-min':
				UI::content()->add('HR::showcase',[
					'template' => 'gallery-min'
				]);
				break;
		}
		if(!URL::page(1)) UI::content()->add('HR::show_page','home');
	}

	public static function show_page($value=''){
		echo TEMPLATE::Render('rubixcode/hotel','home-page',[
			'image'	=>	URL::http("%DATA%image/p (1).png"),
			'image1'	=>	URL::http("%DATA%image/p (2).png"),
			'image2'	=>	URL::http("%DATA%image/parallax.jpg"),
		]);
		UI::footer()->add("<script>$(document).ready(function(){ $('.parallax').parallax(); });</script>");
	}

	public static function registration_form($value=''){
		$data = [];
		$template = "registration-form";
		$rooms = null;
		if(isset($_COOKIE['hotel'])){
			$data = json_decode($_COOKIE['hotel'],true);
			$rooms = HR::get_available_rooms([
				'start' => $data['reservation']['start'],
				'end' => $data['reservation']['end'],
				'output' => array('type','rate'),
			]);
		}
		echo TEMPLATE::Render('rubixcode/hotel',$template,array(
				'details' => [
					'rooms' => $rooms,
				],
			));
	}

	public static function showcase($value=[]){
		$template = isset($value['template'])?$value['template']:"gallery";
		echo MAPLE::Render('hotel','gallery',[]);
	}

	public static function get_available_rooms($param){
		$retval = [];
		$prefix = DB::_()->prefix;
		$sql =
			"SELECT DISTINCT `{$prefix}hr_rooms`.`ID` ,`{$prefix}hr_rooms`.`Number`,`{$prefix}hr_room_type`.`Name`
			FROM `{$prefix}hr_rooms`
			JOIN `{$prefix}hr_room_type`
			ON `{$prefix}hr_room_type`.`ID`=`{$prefix}hr_rooms`.`Type`
			WHERE `{$prefix}hr_rooms`.`ID` NOT IN (
				SELECT `{$prefix}hr_rooms`.`ID`
				FROM `{$prefix}hr_rooms`
				JOIN `{$prefix}hr_reservations`
				ON `{$prefix}hr_reservations`.`Room` = `{$prefix}hr_rooms`.`ID`
				WHERE (
					`{$prefix}hr_reservations`.`C_IN` <= STR_TO_DATE('{$param['from']}','%d %M,%Y')
					AND `{$prefix}hr_reservations`.`C_OUT` >= STR_TO_DATE('{$param['from']}','%d %M,%Y')
				) OR (
					`{$prefix}hr_reservations`.`C_IN` >= STR_TO_DATE('{$param['from']}','%d %M,%Y')
					AND `{$prefix}hr_reservations`.`C_OUT` <= STR_TO_DATE('{$param['to']}','%d %M,%Y')
				) OR (
					`{$prefix}hr_reservations`.`C_IN` <= STR_TO_DATE('{$param['to']}','%d %M,%Y')
					AND `{$prefix}hr_reservations`.`C_OUT` >= STR_TO_DATE('{$param['to']}','%d %M,%Y')
				)
			)
		";
		$sql = DB::Query($sql);
		while ($row = DB::Fetch_Array($sql)) {
			$retval[$row['ID']] = [
				'id'	=>	$row['ID'],
				'type'	=>	$row['Name'],
				'number'=>	$row['Number'],
			];
		}
		return $retval;
	}

	public static function available_category_ajax($param){
		return [];
	}

	public static function add_reservation($value=''){
		$error = false;
		if(URL::has_request(['from','to','from1','to1','room','name','phone','email','passport','address','city','state','zip','comment'])){
			$_REQUEST['from1'] = strtotime($_REQUEST['from']);
			$_REQUEST['to1'] = strtotime($_REQUEST['to']);
			if(!($_REQUEST['from1'] < $_REQUEST['to1']) && !($_REQUEST['from1'] > time()))
				$error = "Invalid Dates selected!";
			else{
				if(!array_key_exists($_REQUEST['room'],HR::get_available_rooms(['from'	=>	$_REQUEST['from'], 'to'	=>	$_REQUEST['to']]))){
					$error = "Invalid Room Selected";
				} else {
					$sql = DB::_()->select("hr_clients",[
						"Email"	=>	$_REQUEST["email"]
					]);
					$sql2 = "";
					if(count($sql)){
						$time = time();
						DB::_()->insert("hr_clients",[
							"Name"	=>	$_REQUEST["name"],
							"Phone"	=>	$_REQUEST["phone"],
							"Email"	=>	$_REQUEST["email"],
							"Address"=>	$_REQUEST["address"],
							"City"	=>	$_REQUEST["city"],
							"Zip"	=>	$_REQUEST["zip"],
							"Passport"	=>	$_REQUEST["passport"],
							# TODO : Seriously!
							"Proof"	=>	$time,
						]);
						$sql2 = DB::_()->id();
					} else {
						$sql2 = $sql['ID'];
					}
					$payment = "0";
					$complete = "0";
					if(SECURITY::has_access("hotel-billing-desk")){
						if(isset($_REQUEST['paid'])) $payment = "1";
						$complete = "1";
					}
					$sql = DB::insert("hr_reservations",[
						"Room"		=>	$_REQUEST["room"],
						"Client"	=>	$sql2,
						"#C_IN"		=>	"STR_TO_DATE('{$_REQUEST["from"]}','%d %M,%Y')",
						"#C_OUT"	=>	"STR_TO_DATE('{$_REQUEST["to"]}','%d %M,%Y')",
						"Payment"	=>	$payment,
						"Comment"	=>	$_REQUEST["comment"],
						"Complete"	=>	$comment,
					]);
				}
			}
		} else {
			$error = "Invalid Parameters.";
		}
		if($error) SESSION::set_var('hotel','reservation_error',$error);
		else{
			MAPLE::DashMessage(['title'=>'Resrvation Successfull!']);
		}
		$_REQUEST['redirect_to'] = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : URL::http("%ROOT%");
		URL::redirect($_REQUEST['redirect_to']);
	}
}
?>
