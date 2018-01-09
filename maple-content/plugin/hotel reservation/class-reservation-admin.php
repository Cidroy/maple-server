<?php
/**
*
*/
class HRA{
	public static function dashboard($value=''){
		# code...
		# booked
		# available
		# views
	}

	public static function home($value=''){
		# code...
		# admin home page
		echo "string";
	}

	public static function rooms_page($value=''){
		$room_type = [];
		$rooms = [];
		$title = "Rooms";
		$prefix = DB::_()->prefix;

		if(isset($_REQUEST['add-category'])){
			echo TEMPLATE::Render('rubixcode/hotel','add-category',[
				'form'	=>	[
					'ajax'	=>	HTML::AjaxInput('hotel','htl_add_category')
				],
			]);
			return;
		}
		else if(isset($_REQUEST['add-room'])){
			$category = [];
			$sql = DB::_()->select("hr_room_type",["ID","Name"]);
			foreach ($sql as $row){
				$category[] = [
					'id'	=>	$row['ID'],
					'name'	=>	$row['Name']
				];
			}
			echo TEMPLATE::Render('rubixcode/hotel','add-room',[
				'content'	=>	[
					'category'	=>	$category,
					'form'		=>	[
						'ajax'	=>	HTML::AjaxInput('hotel','htl_add_room'),
					],
				],
			]);
			return;
		}

		if(isset($_REQUEST['room'])){
			$title = "Room {$_REQUEST["room"]}";
			$sql = DB::_()->count("hr_rooms");
			$prefix = DB::_()->prefix;
			if($sql){
				$room_detail = [];
				$room_bookings = [];
				$room_current = [];
				$room_history = [];

				# present
				$sql = DB::Query("SELECT *
						FROM `{$prefix}hr_reservations`
						INNER JOIN `{$prefix}hr_clients`
						ON `{$prefix}hr_reservations`.`Client` = `{$prefix}hr_clients`.`ID`
						JOIN `{$prefix}hr_rooms`
						ON `{$prefix}hr_rooms`.`ID` = `{$prefix}hr_reservations`.`Room`
						WHERE `{$prefix}hr_rooms`.`Number` = '{$_REQUEST["room"]}'
						AND `C_IN` <= NOW()
						AND `C_OUT` > NOW()
						ORDER BY `C_IN`;
					");
				while ($row = DB::Fetch_Array($sql)) {
					$room_current = [
						'client' => [
							'name'	=> $row['Name'],
							'phone'	=> $row['Phone'],
							'email'	=> $row['Email'],
							'address'	=> $row['Address'],
							'city'	=> $row['City'],
							'state'	=> $row['State'],
							'zip'	=> $row['Zip'],
							'passport'	=> $row['Passport'],
							'proof'	=> json_decode($row['Proof'],true),
						],
						'booking'=> [
							'start'	=> $row['C_IN'],
							'end'	=> $row['C_OUT'],
						],
					];
				}

				# future
				$sql = DB::Query(
					"SELECT *
					FROM `{$prefix}hr_reservations`
					INNER JOIN `{$prefix}hr_clients`
					ON `{$prefix}hr_reservations`.`Client` = `{$prefix}hr_clients`.`ID`
					JOIN `{$prefix}hr_rooms`
					ON `{$prefix}hr_rooms`.`ID` = `{$prefix}hr_reservations`.`Room`
					WHERE `{$prefix}hr_rooms`.`Number` = '{$_REQUEST["room"]}'
					AND `C_IN` > NOW()
					ORDER BY `C_IN`;
				");
				while ($row = DB::Fetch_Array($sql)) {
					$room_bookings[] = [
						'client' => [
							'name'	=> $row['Name'],
							'phone'	=> $row['Phone'],
							'email'	=> $row['Email'],
							'address'	=> $row['Address'],
							'city'	=> $row['City'],
							'state'	=> $row['State'],
							'zip'	=> $row['Zip'],
							'passport'	=> $row['Passport'],
							'proof'	=> json_decode($row['Proof'],true),
						],
						'booking'=> [
							'start'	=> $row['C_IN'],
							'end'	=> $row['C_OUT'],
						],
					];
				}

				# history
				$sql = DB::Query(
					"SELECT *
					FROM `{$prefix}hr_reservations`
					INNER JOIN `{$prefix}hr_clients`
					ON `{$prefix}hr_reservations`.`Client` = `{$prefix}hr_clients`.`ID`
					JOIN `{$prefix}hr_rooms`
					ON `{$prefix}hr_rooms`.`ID` = `{$prefix}hr_reservations`.`Room`
					WHERE `{$prefix}hr_rooms`.`Number` = '{$_REQUEST["room"]}'
					AND `C_OUT` < NOW()
					ORDER BY `C_IN`;
				");
				while ($row = DB::Fetch_Array($sql)) {
					$room_history[] = [
						'client' => [
							'name'	=> $row['Name'],
							'phone'	=> $row['Phone'],
							'email'	=> $row['Email'],
							'address'	=> $row['Address'],
							'city'	=> $row['City'],
							'state'	=> $row['State'],
							'zip'	=> $row['Zip'],
							'passport'	=> $row['Passport'],
							'proof'	=> json_decode($row['Proof'],true),
						],
						'booking'=> [
							'start'	=> $row['C_IN'],
							'end'	=> $row['C_OUT'],
						],
					];
				}
				$sql = "SELECT `{$prefix}hr_room_type`.`Name`,`{$prefix}hr_room_type`.`ID`
						FROM `{$prefix}hr_rooms`
						JOIN `{$prefix}hr_room_type`
						ON `{$prefix}hr_rooms`.`Type` = `{$prefix}hr_room_type`.`ID`
						WHERE `{$prefix}hr_rooms`.`Number` = '{$_REQUEST["room"]}'
						";
				$sql = DB::Fetch_Array(DB::Query($sql));
				echo TEMPLATE::Render('rubixcode/hotel','admin-room-status',[
					'bookings'	=> $room_bookings,
					'current'	=> $room_current,
					'history'	=> $room_history,
					'room'		=> [
						'number'	=> $_REQUEST['room'],
						'type'	=> $sql['Name'],
						'id'	=> $sql['ID'],
					],
					'form'		=>	[
						'explicit_register'	=>	HTML::AjaxInput('hotel','htl_reserve_admin'),
						'delete_room_ajax'	=>	HTML::AjaxInput('hotel','htl_delete_room')
					],
				]);
			}
		}
		else if(isset($_REQUEST['room-type'])){
			$sql = DB::Query(
				"SELECT `{$prefix}hr_room_type`.`Name` AS `Type` ,`{$prefix}hr_rooms`.`Number`
				FROM `{$prefix}hr_rooms`
				INNER JOIN `{$prefix}hr_room_type`
				ON `{$prefix}hr_rooms`.`Type`=`{$prefix}hr_room_type`.`ID`
				WHERE `Number` NOT IN
				(SELECT DISTINCT(`Number`)
				FROM `{$prefix}hr_rooms`
				JOIN `{$prefix}hr_reservations`
				ON `{$prefix}hr_reservations`.`Room`=`{$prefix}hr_rooms`.`ID`
				AND (`{$prefix}hr_reservations`.`C_IN` < NOW())
				AND (`{$prefix}hr_reservations`.`C_OUT`> NOW()))
				AND `{$prefix}hr_rooms`.`Type` = '{$_REQUEST["room-type"]}'
			");
			while($row = DB::Fetch_Array($sql)){
				$rooms[] = [
					'number' => $row['Number'],
					'type' => $row['Type'],
					'available' => true,
					'url'	=> "?room={$row["Number"]}"
				];
			}

			# un available
			$sql = DB::Query(
				"SELECT `{$prefix}hr_roo{$prefix}type`.`Name` AS `Type` ,`{$prefix}hr_rooms`.`Number`
				FROM `{$prefix}hr_rooms`
				INNER JOIN `{$prefix}hr_roo{$prefix}type`
				ON `{$prefix}hr_rooms`.`Type`=`{$prefix}hr_roo{$prefix}type`.`ID`
				WHERE `Number` IN
				(SELECT DISTINCT(`Number`)
				FROM `{$prefix}hr_rooms`
				JOIN `{$prefix}hr_reservations`
				ON `{$prefix}hr_reservations`.`Room`=`{$prefix}hr_rooms`.`ID`
				AND (`{$prefix}hr_reservations`.`C_IN` < NOW())
				AND (`{$prefix}hr_reservations`.`C_OUT`> NOW()))
				AND `{$prefix}hr_rooms`.`Type` = '{$_REQUEST["room-type"]}'
			");
			while($row = DB::Fetch_Array($sql)){
				$rooms[] = [
					'number' => $row['Number'],
					'type' => $row['Type'],
					'url'	=> "?room={$row["Number"]}"
				];
			}

			$sql = DB::_()->select("hr_room_type","*",[
				"ID"	=>	$_REQUEST["room-type"]
			]);
			foreach($sql as $row){
				$title = $row['Name'];
			}
			echo TEMPLATE::Render('rubixcode/hotel','admin-rooms',[
				'content' => [
					'title' => $title,
					'rooms' => $rooms,
					'types'	=> $room_type,
					'meta'	=> [
						'back_all_room'=> true,
					],
				],
				'this'	=> [
					'url'	=>	[
						'add_category'	=> URL::name("hotel/admin","add-category"),
						'add_room'	=> URL::name("hotel/admin","add-room"),
					],
				],
			]);
		}
		else{
			# available
			$sql = DB::Query(
				"SELECT `{$prefix}hr_room_type`.`Name` AS `Type` ,`{$prefix}hr_rooms`.`Number`
				FROM `{$prefix}hr_rooms`
				INNER JOIN `{$prefix}hr_room_type`
				ON `{$prefix}hr_rooms`.`Type`=`{$prefix}hr_room_type`.`ID`
				WHERE `Number` NOT IN
				(SELECT DISTINCT(`Number`)
				FROM `{$prefix}hr_rooms`
				JOIN `{$prefix}hr_reservations`
				ON `{$prefix}hr_reservations`.`Room`=`{$prefix}hr_rooms`.`ID`
				AND (`{$prefix}hr_reservations`.`C_IN` < NOW())
				AND (`{$prefix}hr_reservations`.`C_OUT`> NOW()))
			");
			while($row = DB::Fetch_Array($sql)){
				$rooms[] = [
					'number' => $row['Number'],
					'type' => $row['Type'],
					'available' => true,
					'url'	=> "?room={$row["Number"]}"
				];
			}

			# un available
			$sql = DB::Query(
				"SELECT `{$prefix}hr_room_type`.`Name` AS `Type` ,`{$prefix}hr_rooms`.`Number`
				FROM `{$prefix}hr_rooms`
				INNER JOIN `{$prefix}hr_room_type`
				ON `{$prefix}hr_rooms`.`Type`=`{$prefix}hr_room_type`.`ID`
				WHERE `Number` IN
				(SELECT DISTINCT(`Number`)
				FROM `{$prefix}hr_rooms`
				JOIN `{$prefix}hr_reservations`
				ON `{$prefix}hr_reservations`.`Room`=`{$prefix}hr_rooms`.`ID`
				AND (`{$prefix}hr_reservations`.`C_IN` < NOW())
				AND (`{$prefix}hr_reservations`.`C_OUT`> NOW()))
			");
			while($row = DB::Fetch_Array($sql)){
				$rooms[] = [
					'number' => $row['Number'],
					'type' => $row['Type'],
					'url'	=> "?room={$row["Number"]}"
				];
			}

			$sql = DB::_()->select("hr_room_type","*");
			foreach ($sql as $row) {
				$room_type[] = [
					"name"	=>	$row["Name"],
					'url' => "?room-type={$row["ID"]}",
				];
			}

			echo TEMPLATE::Render('rubixcode/hotel','admin-rooms',[
				'content' => [
					'title' => $title,
					'rooms' => $rooms,
					'types'	=> $room_type,
				],
				'this'	=>	[
					'url'	=>	[
						'add_room'	=>	URL::name("hotel/admin",'add-room'),
						'add_category'	=>	URL::name("hotel/admin",'add-category'),
					],
				],
			]);
		}

	}

	public static function reservations_page($value=''){
		# TODO : Check this code error
		# show who reserved what room and when
		$reserve = [
			'today' => [],
			'tomorrow' => [],
			'week' => [],
		];

		if(URL::has_request(array('view-reservation','id'))){
			$sql = DB::_()->select("hr_reservations",["ID", "Room", "Client", "C_IN", "C_OUT", "Payment", "Comment", "Complete"],[
				"ID"	=>	$_REQUEST["id"]
			]);
			if(!count($sql)){
				MAPLE::DashMessage(array('title'=>'Invalid Transaction was selected.'));
				URL::redirect(URL::name("hotel/admin",'reservation-list'));
				return;
			}
			$client = DB::_()->select("hr_clients",["ID", "Name", "Phone", "Email", "Address", "City", "State", "Zip", "Passport", "Proof"],[
				"ID"	=>	$sql["Client"]
			]);
			$prefix = DB::_()->prefix;
			$room = DB::Fetch_Array(DB::Query(
				"SELECT `{$prefix}hr_rooms`.`ID`, `{$prefix}hr_rooms`.`Number` , `{$prefix}hr_room_type`.`Name` AS `Type`
				FROM `{$prefix}hr_rooms`
				JOIN `{$prefix}hr_room_type`
				ON `{$prefix}hr_room_type`.`ID` = `{$prefix}hr_rooms`.`Type`
				WHERE `{$prefix}hr_rooms`.`ID`='{$sql['Room']}'
			"));
			echo TEMPLATE::Render('rubixcode/hotel','admin-reservation-detail',[
				'form'	=>	[
					'cancel'	=>	HTML::AjaxInput('hotel','htl_cancel_reservation'),
				],
				'content'	=>	[
					'client'	=>	[
						'name'	=>	$client['Name'],
						'id'	=>	$client['ID'],
						'phone'	=>	$client['Phone'],
						'email'	=>	$client['Email'],
						'address'	=>	$client['Address'],
						'city'	=>	$client['City'],
						'state'	=>	$client['State'],
						'zip'	=>	$client['Zip'],
						'passport'	=>	$client['Passport'],
					],
					'reserve'	=>	[
						'from'	=>	$sql['C_IN'],
						'to'	=>	$sql['C_OUT'],
						'id'	=>	$sql['ID'],
					],
					'room'	=>	[
						'number'=>	$room['Number'],
						'type'	=>	$room['Type'],
					],
				],
			]);
			return;
		}

		if(isset($_REQUEST['range'])){
			if(!URL::has_request(['start','end'])){
				$reserve['error']	= [
					'critical'	=> ['Start date and End date are required!'],
				];
			}
			else{
				$reserve['query']	= [
					'start'	=> $_REQUEST['start'],
					'end'	=> $_REQUEST['end'],
					'results'	=> [],
				];
				$prefix = DB::_()->prefix;
				$sql =
				   "SELECT `{$prefix}hr_rooms`.`Number`, `{$prefix}hr_clients`.`Name`,`{$prefix}hr_clients`.`ID` AS `Client_ID`, `{$prefix}hr_reservations`.`C_IN`,`{$prefix}hr_reservations`.`C_OUT`,`{$prefix}hr_reservations`.`ID`
					FROM `{$prefix}hr_reservations`
					JOIN `{$prefix}hr_clients`
					ON `{$prefix}hr_reservations`.`Client`=`{$prefix}hr_clients`.`ID`
					JOIN `{$prefix}hr_rooms` ON `{$prefix}hr_rooms`.`ID` = `{$prefix}hr_reservations`.`Room`
					WHERE
						(	`{$prefix}hr_reservations`.`C_IN` <= STR_TO_DATE('{$_REQUEST["start"]}','%d %M,%Y')
							AND `{$prefix}hr_reservations`.`C_OUT` >= STR_TO_DATE('{$_REQUEST["end"]}','%d %M,%Y')
						)
						OR
						(	`{$prefix}hr_reservations`.`C_IN` >= STR_TO_DATE('{$_REQUEST["start"]}','%d %M,%Y')
							AND `{$prefix}hr_reservations`.`C_IN` <= STR_TO_DATE('{$_REQUEST["end"]}','%d %M,%Y')
						)
				";
				$sql = DB::Query($sql);
				while ($row = DB::Fetch_Array($sql)) {
					$reserve['query']['results'][] = [
						'client'	=> [
							'name'	=> $row['Name'],
							'url'	=> $row['Client_ID'],
						],
						'reserve'	=> [
							'start'	=> $row['C_IN'],
							'end'	=> $row['C_OUT'],
							'id'	=>	$row['ID'],
						],
						'url'		=> [
							'checkout'	=> URL::http("%ROOT%",[
								"maple_ajax"	=>	"hotel",
								"maple_ajax_action"	=>	"checkout_client",
								"transaction"	=>	$row["ID"]
							])
						],
					];
				}
			}
		}
		else{
			# today
			$prefix = DB::_()->prefix;
			$sql = DB::Query(
				"SELECT
					`{$prefix}hr_rooms`.`Number`,
					`{$prefix}hr_clients`.`Name`,`{$prefix}hr_clients`.`ID` AS `Client_ID`,
					`{$prefix}hr_reservations`.`C_IN`,`{$prefix}hr_reservations`.`C_OUT`,`{$prefix}hr_reservations`.`ID`
				FROM `{$prefix}hr_reservations`
				JOIN `{$prefix}hr_clients`
				ON `{$prefix}hr_reservations`.`Client`=`{$prefix}hr_clients`.`ID`
				JOIN `{$prefix}hr_rooms`
				ON `{$prefix}hr_rooms`.`ID` = `{$prefix}hr_reservations`.`Room`
				WHERE `{$prefix}hr_reservations`.`C_IN` <= DATE(NOW())
				AND `{$prefix}hr_reservations`.`C_OUT` >= DATE(NOW())
			");
			while ($row = DB::Fetch_Array($sql)) {
				$reserve['today'][]  = [
					'client'	=> [
						'name'	=> $row['Name'],
						'url'	=> $row['Client_ID'],
					],
					'reserve'	=> [
						'start'	=> $row['C_IN'],
						'end'	=> $row['C_OUT'],
						'id'	=>	$row['ID'],
					],
					'url'		=> [
						'checkout'	=> URL::http("%ROOT%",[
							"maple_ajax"		=>	"hotel",
							"maple_ajax_action"	=>	"checkout_client",
							"transaction"		=>	$row["ID"]
						])
					],
				];
			}
			#tomorrow
			$sql = DB::Query(
				"SELECT
					`{$prefix}hr_rooms`.`Number`,
					`{$prefix}hr_clients`.`Name`,`{$prefix}hr_clients`.`ID` AS `Client_ID`,
					`{$prefix}hr_reservations`.`C_IN`,`{$prefix}hr_reservations`.`C_OUT`,`{$prefix}hr_reservations`.`ID`
				FROM `{$prefix}hr_reservations`
				JOIN `{$prefix}hr_clients`
				ON `{$prefix}hr_reservations`.`Client`=`{$prefix}hr_clients`.`ID`
				JOIN `{$prefix}hr_rooms`
				ON `{$prefix}hr_rooms`.`ID` = `{$prefix}hr_reservations`.`Room`
				WHERE `{$prefix}hr_reservations`.`C_IN` = DATE_ADD(DATE(NOW()),INTERVAL 1 DAY)
				AND `{$prefix}hr_reservations`.`C_OUT` >= DATE_ADD(DATE(NOW()),INTERVAL 2 DAY)
			");
			while ($row = DB::Fetch_Array($sql)) {
				$reserve['tomorrow'] = [
					'client'	=> [
						'name'	=> $row['Name'],
						'url'	=> $row['Client_ID'],
					],
					'reserve'	=> [
						'start'	=> $row['C_IN'],
						'end'	=> $row['C_OUT'],
						'id'	=>	$row['ID'],
					],
					'url'		=> [
						'cancel'	=> URL::http("%ROOT%",[
							"maple_ajax"		=>	"hotel",
							"maple_ajax_action"	=>	"cancel_reservation",
							"transaction"		=>	$row["ID"]
						])
					],
				];
			}
			#week
			$sql = DB::Query(
				"SELECT
					`{$prefix}hr_rooms`.`Number`,
					`{$prefix}hr_clients`.`Name`,`{$prefix}hr_clients`.`ID` AS `Client_ID`,
					`{$prefix}hr_reservations`.`C_IN`,`{$prefix}hr_reservations`.`C_OUT`,`{$prefix}hr_reservations`.`ID`
				FROM `{$prefix}hr_reservations`
				JOIN `{$prefix}hr_clients`
				ON `{$prefix}hr_reservations`.`Client`=`{$prefix}hr_clients`.`ID`
				JOIN `{$prefix}hr_rooms`
				ON `{$prefix}hr_rooms`.`ID` = `{$prefix}hr_reservations`.`Room`
				WHERE `{$prefix}hr_reservations`.`C_IN` < DATE_ADD(DATE(NOW()),INTERVAL 2 DAY)
				AND `{$prefix}hr_reservations`.`C_OUT` >= DATE_ADD(DATE(NOW()),INTERVAL 9 DAY)
			");
			while ($row = DB::Fetch_Array($sql)) {
				$reserve['week'][] = [
					'client'	=> [
						'name'	=> $row['Name'],
						'url'	=> $row['Client_ID'],
					],
					'reserve'	=> [
						'start'	=> $row['C_IN'],
						'end'	=> $row['C_OUT'],
						'id'	=>	$row['ID'],
					],
					'url'		=> [
						'cancel'	=> URL::http("%ROOT%",[
							"maple_ajax"		=>	"hotel",
							"maple_ajax_action"	=>	"cancel_reservation",
							"transaction"		=>	$row["ID"]
						])
					],
				];
			}
		}
		echo TEMPLATE::Render('rubixcode/hotel','admin-reservations',[
			'reservations' => $reserve ,
			'form'			=> [
				'search'		=> HTML::Input('search',[
					'id'	=> 'search',
					'type'	=> 'text',
					'required'	=> true,
				]),
				'ajax_action'	=> HTML::AjaxInput('hotel','search'),
			],
		]);
	}

	public static function client_page($value=''){
		$render = [
			'clients'	=> [],
		];
		$prefix = DB::_()->prefix;
		if(URL::has_request(array('client'))){
			$render = [
				'client'	=>	[],
				'orders'	=>	[],
			];
			$sql = DB::_()->select("hr_clients",[ "ID"	=>	$_REQUEST["client"] ]);

			if(count($sql)){
				$render['client'] = [
					'name'	=> $sql['Name'],
					'phone'	=> $sql['Phone'],
					'email'	=> $sql['Email'],
					'address'	=> $sql['Address'],
					'city'	=> $sql['City'],
					'state'	=> $sql['State'],
					'zip'	=> $sql['Zip'],
					'passport'	=> $sql['Passport'],
				];
				$sql =
					"SELECT *
					FROM `{$prefix}hr_clients`
					JOIN `{$prefix}hr_reservations`
					ON `{$prefix}hr_clients`.`ID`=`{$prefix}hr_reservations`.`Client`
					JOIN `{$prefix}hr_rooms`
					ON `{$prefix}hr_reservations`.`Room`=`{$prefix}hr_rooms`.`ID`
					WHERE `{$prefix}hr_clients`.`ID` = '{$_REQUEST['client']}'
				";
				$sql = DB::Query($sql);
				while ($row = DB::Fetch_Array($sql)) {
					$render['orders'][]  = [
						'start'	=> $row['C_IN'],
						'end'	=> $row['C_OUT'],
						'room'	=> $row['Number'],
					];
				}
			}
			echo TEMPLATE::Render('rubixcode/hotel','admin-client-details',[
				'client'	=> $render['client'],
				'orders'	=> $render['orders'],
			]);
		}
		else{
			$sql = DB_()->select("hr_clients","*");
			foreach ($sql as $row) {
				$render['clients'][]  = [
					'name'	=> $row['Name'],
					'phone'	=> $row['Phone'],
					'email'	=> $row['Email'],
					'address'	=> $row['Address'],
					'url'	=> URL::http("%CURRENT%","?client={$row["ID"]}"),
				];
			}
			echo TEMPLATE::Render('rubixcode/hotel','admin-clients-list',$render);
		}
	}

	public static function rate_page($value=''){
		$prefix = DB::_()->prefix;
		if(URL::has_request(['add'])){
			$render = [
				'room_types'	=> [],
				'form'			=> [
					'ajax_action'	=> HTML::AjaxInput('hotel','insert_rates'),
				],
				'filter'		=> [
					'admin_message'	=> MAPLE::do_filters('admin_message'),
				],
			];
			$sql = DB::_()->select("hr_room_type",["ID","Name"]);
			foreach ($sql as $row) {
				$render['room_types'][]  = [
					'id'	=>	$row['ID'],
					'name'	=>	$row['Name'],
				];
			}
			echo TEMPLATE::Render('rubixcode/hotel','admin-rates-form',$render);
		}
		else if(URL::has_request(array('search','date'))){
			$render = [
				'span'		=> [],
				'filter'	=> [
					'pre_admin_rate_sheet'	=>	MAPLE::do_filters("pre_admin_rate_sheet"),
				],
				'search'	=> $_REQUEST['date'],
			];
			$sql =
				"SELECT DISTINCT `Starts`,`Ends`,`ID`
				FROM `{$prefix}hr_rates`
				WHERE  `Starts`	<= STR_TO_DATE('{$_REQUEST["date"]}','%d %M,%Y')
				AND 	`Ends`	>= STR_TO_DATE('{$_REQUEST["date"]}','%d %M,%Y')
				ORDER BY `Starts`
			";
			$sql = DB::Query($sql);
			while ($row = DB::Fetch_Array($sql)) {
				$render['span'][] = [
					'start'	=> $row['Starts'],
					'end'	=> $row['Ends'],
					'url'	=> [
						'view'	=>	URL::http("?view&id=$row[ID]"),
						'edit'	=>	URL::http("?edit&id=$row[ID]"),
						'delete'	=>	URL::http("?delete&id=$row[ID]"),
					],
				];
			}
			echo TEMPLATE::Render('rubixcode/hotel','admin-rates-list',$render);
		}
		else if(URL::has_request(array('edit','id'))){
			$render = [
				'room_types'	=> [],
				'form'			=> [
					'ajax_action'	=> HTML::AjaxInput('hotel','update_rates'),
				],
				'filter'		=> [
					'admin_message'	=> MAPLE::do_filters('admin_message'),
				],
			];

			$sql = DB::_()->select("hr_rates",["ID","Starts","Ends"],[
				"ID"	=>	$_REQUEST["id"]
			]);
			if(count($sql)){
				$sql = $sql[0];
				$render['span']	= [
					'start'	=> $sql['Starts'],
					'end'	=> $sql['Ends'],
					'url'	=> [
						'cancel'	=> "?",
					],
				];
				$sql =
					"SELECT `{$prefix}hr_room_type`.`ID`,`{$prefix}hr_room_type`.`Name`,`{$prefix}hr_rates`.`Rate`,`{$prefix}hr_rates`.`Comment`
					FROM `{$prefix}hr_room_type`
					JOIN `{$prefix}hr_rates`
					ON `{$prefix}hr_room_type`.`ID` = `{$prefix}hr_rates`.`Type`
					WHERE `Starts` = '{$render["span"]["start"]}'
					AND `Ends` = '{$render["span"]["end"]}'
				";
				$sql = DB::Query($sql);
				while ($row = DB::Fetch_Array($sql)) {
					$render['room_types'][] = [
						'id'	=>	$row['ID'],
						'name'	=>	$row['Name'],
						'rate'	=>	$row['Rate'],
						'comment'	=>	$row['Comment'],
					];
				}
			}
			echo TEMPLATE::Render('rubixcode/hotel','admin-rates-update-form',$render);
		}
		else if(URL::has_request(array('view','id'))){
			$render = [];
			$sql = DB::_()->select("hr_rates",["ID","Starts","Ends"],[
				"ID"	=>	$_REQUEST["id"]
			]);
			if(count($sql)){
				$sql = $sql[0];
				$render['span']	= [
					'start'	=> $sql['Starts'],
					'end'	=> $sql['Ends'],
					'url'	=> [
						'edit'	=> URL::http("",[
							"edit"	=>	true,
							"id"	=>	$sql["ID"]
						]),
					],
				];

				$sql =
					"SELECT `{$prefix}hr_rates`.`Rate`,`{$prefix}hr_room_type`.`Name`,`{$prefix}hr_rates`.`Comment`
					FROM `{$prefix}hr_rates`
					JOIN `{$prefix}hr_room_type`
					ON `{$prefix}hr_rates`.`Type` = `{$prefix}hr_room_type`.`ID`
					WHERE `Starts` = '{$render["span"]["start"]}'
					AND `Ends` = '{$render["span"]["end"]}'
				";
				$sql = DB::Query($sql);
				$render['rooms'] = [];
				while ($row = DB::Fetch_Array($sql)) {
					$render['rooms'][]  = [
						'type'	=> $row['Name'],
						'rate'	=> $row['Rate'],
						'comment'	=> $row['Comment'],
					];
				}
			}
			echo TEMPLATE::Render('rubixcode/hotel','admin-rate-view',$render);
		}
		else{
			$render = array(
					'span'		=> [],
					'filter'	=> [
						'pre_admin_rate_sheet'	=>	MAPLE::do_filters("pre_admin_rate_sheet"),
					],
				);
			$sql =
				"SELECT DISTINCT `Starts`,`Ends`,`ID`
				FROM `{$prefix}hr_rates`
				WHERE `Starts`	> DATE(NOW())
				OR ( `Starts` < DATE(NOW()) AND `Ends` > DATE(NOW()) )
				ORDER BY `Starts`
			";
			$sql = DB::Query($sql);
			while ($row = DB::Fetch_Array($sql)) {
				$render['span'][] = [
					'start'	=> $row['Starts'],
					'end'	=> $row['Ends'],
					'url'	=> [
						'view'	=>	URL::http("?view&id=$row[ID]"),
						'edit'	=>	URL::http("?edit&id=$row[ID]"),
						'delete'	=>	URL::http("?delete&id=$row[ID]"),
					],
				];
			}
			echo TEMPLATE::Render('rubixcode/hotel','admin-rates-list',$render);
		}
	}

	public static function checkout_client(){
		# code...
	}

	public static function cancel_reservation(){
		# code...
	}

	public static function insert_rates($value=''){

		// TODO :

		$render = [
			'alert' =>  [
				'type'	=> '',
				'heading'	=> '',
				'content'	=> '',
				'action'	=> [
					'onDismiss'	=> ''
				],
			]
		];
		if(URL::has_request(array('start-date','end-date','room'))){

		}
		else{
			echo "Something went wrong";
		}
	}

	public static function update_transaction(){
		# code...
	}

	public static function insert_category($value=''){
		if(URL::has_request(['name'])){
			$sql = DB::_()->count("hr_room_type",[
				"Name"	=>	$_REQUEST["name"]
			]);
			if($sql){
				$sql = DB::_()->insert("hr_room_type",[
					"Name"	=>	$_REQUEST["name"]
				]);
				if($sql)  	MAPLE::DashMessage(['title'=>'Categoy Added Successfully']);
				else  		MAPLE::DashMessage(['title'=>'Categoy Added Successfully']);
				URL::redirect(URL::name("hotel/admin",'admin-rooms'));
				return;
			}
			MAPLE::DashMessage(array('title'=>'Category already exists'));
			URL::redirect(URL::name("hotel/admin",'admin-rooms'));
			return;
		}
		MAPLE::DashMessage([
			'title'	=>	'Invalid Parameters',
		]);
		URL::redirect(URL::name("hotel/admin","add-category"));
	}

	public static function insert_room(){
		if(URL::has_request(['name','category'])){
			$sql = DB::_()->count("hr_room_type",[
				"ID"	=>	$_REQUEST["category"]
			]);
			if($sql){
				MAPLE::DashMessage(['title'=>"Invalid Category Selected!"]);
				URL::redirect(URL::name("hotel/admin",'add-room'));
				return;
			}
			$sql = DB::_()->insert("hr_rooms",[
				"Type"	=>	$_REQUEST["category"],
				"Name"	=>	$_REQUEST["name"],
			]);
			if($sql)
				MAPLE::DashMessage([
					'title'=>'Successfully added room',
					'message'=>"{$_REQUEST['name']} was added Successfully!",
				]);
			else
				MAPLE::DashMessage([
					'title'=>"Room {$_REQUEST['name']} not Added",
					'message'=>"Something Went Wrong",
				]);
			URL::redirect(URL::name("hotel/admin",'admin-rooms'));
		}else{
			MAPLE::DashMessage(["title"=>"Incomplete data provided!"]);
			URL::redirect(URL::name("hotel/admin",'add-room'));
		}
	}

	public static function admin_reserve_room(){
		print_r($_REQUEST);
		URL::redirect(URL::name("hotel/admin","register-page",[
			"id"	=>	$_REQUEST["room"]
		]));
	}

	public static function reserve_home($value=''){
		$ajax = false;
		$show_card = [];
		$rooms_available = [];
		$error = "";
		if(URL::has_request(['from','to'])){
			if(!($_REQUEST['from1'] < $_REQUEST['to1']) && !($_REQUEST['from1'] > time()))
				$error = "Invalid Dates selected!";
			else{
				$show_card['2'] = true;
				$rooms_available = HR::get_available_rooms([
					'from'	=>	$_REQUEST['from'],
					'to'	=>	$_REQUEST['to'],
				]);
				if(URL::has_request(['room'])){
					if(isset($rooms_available[$_REQUEST['room']])){
						$rooms_available[$_REQUEST['room']]['selected'] = "selected";
						$show_card['finish'] = true;
						$ajax = HTML::AjaxInput('hotel','htl_add_reservation');
					}
					else $error = "Invalid Room selected";
				}
			}
		}
		echo TEMPLATE::Render('rubixcode/hotel','admin-reserve-page',[
			'form'	=>	[
				'ajax'	=>	$ajax,
			],
			'show_card' => $show_card,
			'error'		=>	$error,
			'request'	=>	$_REQUEST,
			'rooms'		=>	$rooms_available,
		]);
	}

	public static function admin_cancel_reservatin($value=''){
		if(!URL::has_request(['id'])){
			MAPLE::DashMessage([
				'title'	=>	'Invalid Parameters!',
				"type"	=>	"error"
			]);
		} else {
			DB::_()->delete("hr_reservations",[
				"ID"	=>	$_REQUEST["id"]
			]);
			MAPLE::DashMessage([
				'title'	=>	'The reservation has been deleted!',
				"type"	=>	"success"
			]);
		}
		URL::redirect(URL::name("hotel/admin",'reservation-list'));
	}
}
?>
