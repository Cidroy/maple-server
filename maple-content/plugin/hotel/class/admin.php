<?php
namespace rubixcode\hotel;

class ADMIN{

	public static function h_home(){
		echo \TEMPLATE::Render("rubixcode/hotel","admin/home",[
			"images"	=>	[
				"parallax"	=>	\URL::http(__DIR__."/../images/parallax.jpg"),
			],
			"functions"	=>	[
				[ "name"	=>	"Rooms", "url"	=>	\URL::name("rubixcode/hotel/admin","rooms") ]
			]
		]);
	}

	public static function h_rooms(){
		if(\URL::has_request(["add"])){		self::h_rooms_add(); return;	}
		if(\URL::has_request(["edit"])){	self::h_rooms_edit(); return;	}
		if(\URL::has_request(["room"])){	self::h_rooms_detail(); return;	}
		if(\URL::has_request(["reserve"])){	self::h_rooms_reserve(); return;	}

		$page_title = "Rooms";
		$meta = [
			"go_back" => false,
		];
		$category = ROOM::get_categories();
		$rooms = ROOM::get_all();
		echo \TEMPLATE::Render("rubixcode/hotel","admin/rooms",[
			"title" =>	$page_title,
			"rooms"	=>	$rooms,
			"category"	=>	$category,
			"url"	=>	[
				"rooms"	=>	[
					"add"	=>	\URL::name("rubixcode/hotel/admin","rooms-add"),
					"detail"=>	\URL::name("rubixcode/hotel/admin","rooms-detail")
				],
				"category"	=>	[
					"add"	=>	\URL::name("rubixcode/hotel/admin","category-add"),
					"detail"=>	\URL::name("rubixcode/hotel/admin","category-detail")
				]
			],
			"meta"	=>	[
				"go_back"	=>	$meta["go_back"]
			]
		]);
	}

	public static function h_rooms_detail(){
		$details = ROOM::get_room_details(["id" => $_REQUEST["id"]]);
		$details["image"]	= \URL::http(__DIR__."/../images/parallax.jpg");
		echo \TEMPLATE::Render("rubixcode/hotel","admin/rooms-detail",[
			"rooms"	=>	$details
		]);
	}

	public static function h_rooms_add(){
		$category = ROOM::get_categories();
		if(!$category){
			\MAPLE::DashMessage([
				"title"	=>	"Please Add Room Types",
				"message"=> "there is currently no room type to assign room to",
				"type"	=>	"error",
			]);
			\URL::redirect(\URL::name("rubixcode/hotel/admin","category-add"));
			return;
		}
		echo \TEMPLATE::Render('rubixcode/hotel','admin/rooms-add',[
			"title"		=>	"Add Rooms",
			'content'	=>	[
				'category'	=>	$category,
				'form'		=>	[
					'ajax'	=>	\HTML::AjaxInput('hotel','hotel-rooms-add'),
				],
			],
		]);
	}

	public static function h_rooms_edit(){
		if(!in_array($_REQUEST["edit"],["id","number"]) && isset($_REQUEST[$_REQUEST["edit"]]) ){
			\MAPLE::DashMessage([
				"type"	=>	"error",
				"title"	=>	"Something went wrong!",
				"message"=>	"invalid parameters"
			]);
			\URL::redirect(\URL::name("rubixcode/hotel/admin","rooms"));
			return;
		}
		$category = ROOM::get_categories();
		if(!$category){
			\MAPLE::DashMessage([
				"title"	=>	"Please Add Room Types",
				"message"=> "there is currently no room type to assign room to",
				"type"	=>	"error",
			]);
			\URL::redirect(\URL::name("rubixcode/hotel/admin","category-add"));
			return;
		}
		$res = \DB::_()->select("hr_rooms","*",["".$_REQUEST["edit"] => $_REQUEST[$_REQUEST["edit"]] ]);
		if(!$res){
			\MAPLE::DashMessage([
				"type"	=>	"error",
				"title"	=>	"Invalid Room selected"
			]);
			\URL::redirect(\URL::name("rubixcode/hotel/admin","rooms"));
			return;
		}
		$_REQUEST = array_merge($_REQUEST,$res[0]);
		$_REQUEST["type"] = ROOM::get_room_type_details(["id" => $_REQUEST["type"]]);
		echo \TEMPLATE::Render('rubixcode/hotel','admin/rooms-add',[
			"title"		=>	"Edit Room",
			'content'	=>	[
				'category'	=>	$category,
				'form'		=>	[
					'ajax'	=>	\HTML::AjaxInput('hotel','hotel-rooms-edit').
								\HTML::Input("edit",["value"	=>	$_REQUEST["edit"],"hidden"=>	true,]).
								\HTML::Input("id",["value"	=>	$_REQUEST["id"],"hidden"=>	true,]),
				],
			],
			"url"	=>	[
				"rooms"	=>	[
					"delete"	=>	\URL::name("rubixcode/hotel/admin","rooms-delete",["id" => $_REQUEST["id"]])
				]
			]
		]);
	}

	public static function h_rooms_reserve(){
		$error = false;
		if(!isset($_REQUEST[$_REQUEST["reserve"]]) || !in_array($_REQUEST["reserve"],["id","name"]) )
			$error = [ "type"	=>	"error", "title"	=>	"insufficient parameters" ];
		if(!\DB::_()->count("hr_rooms",[ "".$_REQUEST["reserve"] => $_REQUEST[$_REQUEST["reserve"]] ]))
			$error = ["type" => "error","title" => "Invalid Room Selection"];
		if($error){
			\MAPLE::DashMessage($error);
			\URL::redirect(\URL::name("rubixcode/hote/admin","rooms"));
			return;
		}
		$details = ROOM::get_room_details(["id" => $_REQUEST["id"]]);
		$details["image"]	= \URL::http(__DIR__."/../images/parallax.jpg");

		$request = [
			"reserve"	=>	$_REQUEST["reserve"],
			"".$_REQUEST["reserve"] => $_REQUEST[$_REQUEST["reserve"]]
		];
		$users = [];
		$client = [];
		$errors = [];
		$transaction = false;
		$payment = false;
		if(isset($_REQUEST["from"])){
			$payment = PAYMENT::html(["amount" => RATES::get_by_room([
				"check_in"	=>	$_REQUEST['from'],
				"id"		=>	$_REQUEST["id"],
				"check_out" =>	$_REQUEST["to"]
			])]);
		}
		if(isset($_REQUEST["client-type"])){
			$request["client-type"] =	$_REQUEST["client-type"];
			switch ($request["client-type"]) {
				case 'existing':
					$users = \DB::_()->select("users",["ID(id)","Name(name)","Email(email)","Login(login)","Gender(gender)","Age(age)","Phone(phone)","Address(address)"]);
					break;
			}
		}
		if(isset($_REQUEST["finish"])){
			$transaction = PAYMENT::get_transation_from_request();
			if($transaction){
				$client = 0;
				if($_REQUEST["client-type"] == "new"){
					$client = CLIENT::add_new($_REQUEST["client"]);
					\MAPLE::DashMessage($client);
					$client = isset($client["id"])?$client["id"]:0;
				}
				if($_REQUEST["client-type"]=="existing" || $_REQUEST["client-type"]=="selected")
					$client = $_REQUEST["client"];
				$val = RESERVATION::reserve_room([
					"room"	=>	$_REQUEST["id"],
					"client"=>	$client,
					"check_in"	=>	$_REQUEST["from"],
					"check_out"	=>	$_REQUEST["to"],
					"payment"	=>	$transaction,
					"comment"	=>	$_REQUEST["comment"]
				]);
				\MAPLE::DashMessage($val);
				if($val["type"]=="success"){
					$xclient = CLIENT::get_details(["id" => $client]);
					\MAIL::Send($xclient["email"],"Room booking info",\TEMPLATE::Render("rubixcode/hotel","admin/mail-desk-checkin",[
						"room"		=>	ROOM::get_room_details(["id"	=>	$_REQUEST["id"]]),
						"client"	=>	$xclient,
						"check_in"	=>	$_REQUEST["from"],
						"check_out"	=>	$_REQUEST["to"],
					]));
					\URL::redirect(\URL::name("rubixcode/hotel/admin","rooms-detail",["id" => $_REQUEST["id"]]));
				}
			} else {
				$errors[] = [
					"type"	=>	"error",
					"title" =>	"Payment Failed!",
					"message"=>	"Please recheck your Payment Details"
				];
			}
		}
		if(isset($_REQUEST["client"]) && !is_array($_REQUEST["client"])){
			$client = CLIENT::get_details(["id" => $_REQUEST["client"]]);
			if($client) $_REQUEST["client-type"] = "selected";
			else {
				$errors[] = [
					"type"	=>	"error",
					"title" =>	"Client Selection Failed",
					"message"=>	"Please recheck your Client Selection"
				];
			}
		}
		if( (isset($_REQUEST["client-type"]) && $_REQUEST["client-type"]=="selected") || ( isset($_REQUEST["client"]) && $_REQUEST["client-type"] == "new" ) ){
			$_REQUEST["finish"] = true;
		}
		if(isset($_REQUEST["finish"])) $request["finish"] = $_REQUEST["finish"];
		echo \TEMPLATE::render("rubixcode/hotel","admin/reserve-by-room",[
				"rooms"		=>	$details,
				"clients" 	=> $users,
				"client"	=>	$client,
				"errors"	=>	$errors,
				"payment"	=>	$payment,
				"form"	=>	[
					"request"	=>	$request
				],
				"url"	=>	[
					"current"	=>	\URL::http("%CURRENT%")
				]
		]);
		\HTML::DataTable("#clients-datatable");
	}

	public static function h_category(){
		if(\URL::has_request(["add"])){	self::h_category_add(); return;	}
		if(\URL::has_request(["edit"])){self::h_category_edit(); return;	}
		if(\URL::has_request(["details"])){self::h_category_details(); return;	}
		$render = [];
		$render["category"] = ROOM::get_categories();
		$render["image"]	= \URL::http(__DIR__."/../images/parallax.jpg");
		$render["url"]		= [
			"add"	=>	\URL::name("rubixcode/hotel/admin","category-add"),
			"detail"=>	\URL::name("rubixcode/hotel/admin","category-detail"),
		];
		echo \TEMPLATE::Render("rubixcode/hotel","admin/category",$render);
	}

	public static function h_category_details(){
		$details = ROOM::get_room_type_details(["id" => $_REQUEST["id"]]);
		$details["image"]	= \URL::http(__DIR__."/../images/parallax.jpg");
		$details["rooms"]	= array_merge(
			ROOM::get_all(["type"	=>	$_REQUEST["id"]])
		);
		echo \TEMPLATE::Render("rubixcode/hotel","admin/category-detail",[
			"category"	=>	$details
		]);
	}

	public static function h_category_add(){
		echo \TEMPLATE::Render('rubixcode/hotel','admin/category-add',[
			'form'	=>	[
				'ajax'	=>	\HTML::AjaxInput('rubixcode/hotel','hotel-category-add')
			],
		]);
	}

	public static function h_category_edit(){
		if(!in_array($_REQUEST["edit"],["id","name"]) && isset($_REQUEST[$_REQUEST["edit"]]) ){
			\MAPLE::DashMessage([
				"type"	=>	"error",
				"title"	=>	"Something went wrong!",
				"message"=>	"invalid parameters"
			]);
			\URL::redirect(\URL::name("rubixcode/hotel/admin","category"));
			return;
		}

		$res = \DB::_()->select("hr_room_type","*",[
			"".$_REQUEST["edit"]	=>	$_REQUEST[$_REQUEST["edit"]]
		]);
		if($res) $_REQUEST = array_merge($_REQUEST,$res[0]);
		else{
			\MAPLE::DashMessage([
				"type"	=>	"error",
				"title"	=>	"Something went wrong!",
				"message"=>	"invalid parameters"
			]);
			\URL::redirect(\URL::name("rubixcode/hotel/admin","category"));
			return;
		}

		echo \TEMPLATE::Render('rubixcode/hotel','admin/category-add',[
			"title"	=>	"Edit Room Type",
			'form'	=>	[
				'ajax'	=>	\HTML::AjaxInput('rubixcode/hotel','hotel-category-edit').
							\HTML::Input("edit",["value"	=>	$_REQUEST["edit"],"hidden"=>	true,]).
							\HTML::Input("id",["value"	=>	$_REQUEST["id"],"hidden"=>	true,])
			],
			"url"	=>	[
				"category"	=>	[
					"delete"	=>	\URL::name("rubixcode/hotel/admin","category-delete",[
						"id"	=>	$_REQUEST["id"]
					])
				]
			]
		]);
	}

	public static function h_reservation(){
		if(isset($_REQUEST["reservation"])) {	self::h_reservation_detail(); return;	}
		if(isset($_REQUEST["checkout"])) {	self::h_reservation_checkout(); return;	}
		if(isset($_REQUEST["cancel"])) {	self::h_reservation_cancel(); return;	}

		$render = [];
		if(isset($_REQUEST["history"])){
			$render = RESERVATION::get_timeline("<");
			$render["title"] = "Reservation History";
		}
		else{
			$render = RESERVATION::get_timeline();
			$render["title"] = "Reservation Current";
		}
		$render = array_merge([	"image"	=>	\URL::http(__DIR__."/../images/parallax.jpg")],$render);
		echo \TEMPLATE::Render("rubixcode/hotel","admin/reservation",$render);
	}

	public static function h_reservation_detail(){
		$res = RESERVATION::details(["".$_REQUEST["reservation"]	=>	$_REQUEST[$_REQUEST["reservation"]]]);
		echo \TEMPLATE::render("rubixcode/hotel","admin/reservation-detail",[
			"reservation"	=>	$res
		]);
	}

	public static function h_reservation_checkout(){
		echo \TEMPLATE::Render("rubixcode/hotel","admin/reservation-checkout",[
			"reservation" => RESERVATION::details(["".$_REQUEST["checkout"]	=>	$_REQUEST[$_REQUEST["checkout"]]]),
			"form"		  => \HTML::AjaxInput("rubixcode/hotel","hotel-reservation-checkout").
							 \HTML::Input("id",["value"	=>	$_REQUEST[$_REQUEST["checkout"]],"hidden"	=>	true,"type"		=>	"text" ])
		]);
	}

	public static function h_reservation_cancel(){
		echo \TEMPLATE::Render("rubixcode/hotel","admin/reservation-cancel",[
			"reservation" => RESERVATION::details(["".$_REQUEST["cancel"]	=>	$_REQUEST[$_REQUEST["cancel"]]]),
			"form"		  => \HTML::AjaxInput("rubixcode/hotel","hotel-reservation-cancel").
							 \HTML::Input("id",["value"	=>	$_REQUEST[$_REQUEST["cancel"]],"hidden"	=>	true,"type"		=>	"text" ])
		]);
	}

	public static function h_clients(){
		if(isset($_REQUEST["client"])){	self::h_clients_detail(); return;	}

		$clients = CLIENT::all();
		echo \TEMPLATE::Render("rubixcode/hotel","admin/client",[
			"clients"	=>	$clients,
			"image"		=>	\URL::http(__DIR__."/../images/parallax.jpg"),
			"url"		=>	[
				"detail"	=>	\URL::name("rubixcode/hotel/admin","client-detail")
			]
		]);
	}

	public static function h_clients_detail(){
		echo \TEMPLATE::Render("rubixcode/hotel","admin/client-detail",[
			"client"	=>	CLIENT::get_details([	"".$_REQUEST["client"] =>	$_REQUEST[$_REQUEST["client"]]	]),
			"orders"	=>	RESERVATION::by_client([	"".$_REQUEST["client"] =>	$_REQUEST[$_REQUEST["client"]]	]),
			"image"		=>	\URL::http(__DIR__."/../images/parallax.jpg"),
		]);
	}

	public static function h_prices(){
		if(isset($_REQUEST["add"])) {	self::h_prices_add() ; return; 	}
		if(isset($_REQUEST["edit"])) {	self::h_prices_edit() ; return; 	}
		if(isset($_REQUEST["delete"])) {	self::h_prices_delete() ; return; 	}

		$res = RATES::seasons();
		echo \TEMPLATE::Render("rubixcode/hotel","admin/prices",[
			"seasons"	=>	$res,
			"image"	=>\URL::http(__DIR__."/../images/parallax.jpg"),
			"url"	=>	[
				"add"	=>	\URL::name("rubixcode/hotel/admin","rates-add")
			]
		]);
	}

	public static function h_prices_add(){
		$rooms = ROOM::get_categories();
		echo \TEMPLATE::Render("rubixcode/hotel","admin/prices-add",[
			"types" => $rooms,
			"form"	=>	\HTML::AjaxInput("rubixcode/hotel","hotel-prices-add"),
		]);
	}

	public static function h_prices_edit(){
		$res = RATES::season_detail(["starts" => $_REQUEST["begin"]]);
		echo \TEMPLATE::Render("rubixcode/hotel","admin/prices-edit",[
			"season"	=>	$res,
			"form"		=>	\HTML::AjaxInput("rubixcode/hotel","hotel-prices-edit").
							\HTML::Input("begin",["type"	=>	"text","value"	=>	$_REQUEST["begin"],"hidden"=>	true])
		]);
	}

	public static function h_prices_delete(){
		$res = RATES::season_detail(["starts" => $_REQUEST["begin"]]);
		echo \TEMPLATE::Render("rubixcode/hotel","admin/prices-delete",[
			"season"	=>	$res,
			"form"		=>	\HTML::AjaxInput("rubixcode/hotel","hotel-prices-delete").
							\HTML::Input("begin",["type"	=>	"text","value"	=>	$_REQUEST["begin"],"hidden"=>	true])
		]);
	}

	public static function h_search(){
		$results = [];
		$users = [];
		$reservations = [];
		if(\URL::has_request(["search"]) && $_REQUEST["search"]){
			$searchers = explode(' ',$_REQUEST["search"]);
			$users = \DB::_()->select("users",["Name(name)","Email(email)","Login(login)","Phone(phone)","Address(address)","ID(id)"],[
				"OR"	=>	[
					"Name[~]"	=>	$searchers,
					"Email[~]"	=>	$searchers,
					"Login[~]"	=>	$searchers,
					"Phone[~]"	=>	$searchers,
					"Address[~]"=>	$searchers,
				]
			]);
			$temp = \DB::_()->select("users","ID(id)",[
				"OR"	=>	[
					"Name[~]"	=>	$searchers,
					"Email[~]"	=>	$searchers,
					"Login[~]"	=>	$searchers,
					"Phone[~]"	=>	$searchers,
					"Address[~]"=>	$searchers,
				]
			]);
			$reservations = \DB::_()->select("hr_reservations",[
				"[>]hr_rooms"	=>	["room" => "id"],
				"[>]users"		=>	["client" => "ID"],
			],[
				"hr_reservations.id",
				"hr_reservations.check_in",
				"hr_reservations.check_out",
				"room"	=>	[
					"hr_rooms.id(room-id)",
					"hr_rooms.number(number)",
				],
				"client"	=> [
					"users.ID(client-id)",
					"users.Name(name)",
					"users.Email(email)",
				]
			],[
				"OR"	=>	[
					"hr_reservations.id[~]"	=>	$searchers,
					"hr_reservations.comment[~]"	=>	$searchers,
					"hr_reservations.check_in[~]"	=>	$searchers,
					"hr_reservations.check_out[~]"	=>	$searchers,
					"hr_reservations.client[~]"		=>	$temp?$temp:["###################"],
				]
			]);
		}else{
			\UI::title()->add("Search");
		}
		\ADMIN::add_content([
			"parent"	=>	"rubixcode/hotel",
			"content"	=> \TEMPLATE::Render("rubixcode/hotel","admin/search",[
				"results"	=>	[
					"count"	=> count($users) + count($reservations),
					"clients"		=>	$users,
					"reservations"		=>	$reservations,
				],
				"url"		=>	[
					"client"	=>	["detail" => \URL::name("rubixcode/hotel/admin","client-detail")],
					"room"		=>	["detail" => \URL::name("rubixcode/hotel/admin","rooms-detail")],
					"reservation"=>	["detail" => \URL::name("rubixcode/hotel/admin","reservation-detail")],
				]
			])
		],true);
	}

	public static function r_reservation_checkout(){
		$res = RESERVATION::checkout(["id"	=>	$_REQUEST["id"]]);
		\MAPLE::DashMessage($res);
		\URL::redirect(\URL::name("rubixcode/hotel/admin","reservation"));
	}

	public static function r_reservation_cancel(){
		$res = RESERVATION::cancel(["id"	=>	$_REQUEST["id"]]);
		\MAPLE::DashMessage($res);
		\URL::redirect(\URL::name("rubixcode/hotel/admin","reservation"));
	}

	public static function r_category_add(){
		$ret = ROOM::add_room_type($_REQUEST);
		\MAPLE::DashMessage($ret);
		$redirect = \URL::name("rubixcode/hotel/admin","category-add");
		if($ret["type"] == "error"){
			unset($_REQUEST["maple_ajax"]);
			unset($_REQUEST["maple_ajax_action"]);
			$redirect = \URL::name("rubixcode/hotel/admin","category-add",$_REQUEST);
		}
		\URL::redirect($redirect);
	}

	public static function r_category_edit(){
		$ret = ROOM::edit_room_type($_REQUEST);
		\MAPLE::DashMessage($ret);
		$redirect = \URL::name("rubixcode/hotel/admin","category");
		if($ret["type"] == "error"){
			unset($_REQUEST["maple_ajax"]);
			unset($_REQUEST["maple_ajax_action"]);
			$redirect = \URL::name("rubixcode/hotel/admin","category-edit",$_REQUEST);
		}
		\URL::redirect($redirect);
	}

	public static function r_category_delete(){
		$ret = ROOM::delete_room_type($_REQUEST);
		\MAPLE::DashMessage($ret);
		$redirect = \URL::name("rubixcode/hotel/admin","category");
		if($ret["type"] == "error"){
			unset($_REQUEST["maple_ajax"]);
			unset($_REQUEST["maple_ajax_action"]);
			$_REQUEST["edit"]	= "id";
			$_REQUEST["id"]		= $_REQUEST["id"];
			$redirect = \URL::name("rubixcode/hotel/admin","category",$_REQUEST);
		}
		\URL::redirect($redirect);
	}

	public static function r_rooms_add(){
		$ret = ROOM::add_rooms($_REQUEST);
		\MAPLE::DashMessage($ret);
		$redirect = \URL::name("rubixcode/hotel/admin","rooms-add");
		if($ret["type"] == "error"){
			unset($_REQUEST["maple_ajax"]);
			unset($_REQUEST["maple_ajax_action"]);
			$redirect = \URL::name("rubixcode/hotel/admin","rooms-add",$_REQUEST);
		}
		\URL::redirect($redirect);
	}

	public static function r_rooms_edit(){
		$ret = ROOM::edit_rooms($_REQUEST);
		\MAPLE::DashMessage($ret);
		$redirect = \URL::name("rubixcode/hotel/admin","rooms");
		if($ret["type"] == "error"){
			unset($_REQUEST["maple_ajax"]);
			unset($_REQUEST["maple_ajax_action"]);
			$redirect = \URL::name("rubixcode/hotel/admin","rooms-edit",$_REQUEST);
		}
		\URL::redirect($redirect);
	}

	public static function r_rooms_delete(){
		$ret = ROOM::delete_rooms($_REQUEST);
		\MAPLE::DashMessage($ret);
		$redirect = \URL::name("rubixcode/hotel/admin","rooms");
		if($ret["type"] == "error"){
			unset($_REQUEST["maple_ajax"]);
			unset($_REQUEST["maple_ajax_action"]);
			$_REQUEST["edit"]	= "id";
			$_REQUEST["id"]		= $_REQUEST["id"];
			$redirect = \URL::name("rubixcode/hotel/admin","rooms",$_REQUEST);
		}
		\URL::redirect($redirect);
	}

	public static function r_prices_add(){
		$rooms = ROOM::get_categories();
		unset($_REQUEST["maple_ajax"]);
		unset($_REQUEST["maple_ajax_action"]);
		if(array_diff(array_keys($rooms),array_keys($_REQUEST["type"]))){
			\MAPLE::DashMessage([
			   "type"	=>	"error",
			   "title"	=>	"insufficient parameters",
			   "message"=>	"Please Fill all the details"
		   ]);
		   \URL::redirect(\URL::name("rubixcode/hotel/admin","rates-add",$_REQUEST));
		   return ;
		}

		$starts = \TIME::parse($_REQUEST["starts"]);
		$ends 	= \TIME::parse($_REQUEST["ends"]);
		if($starts->diffInDays($ends) >= 14 && $starts->lt($ends)){
			$ret = RATES::season_add([
				"starts"	=>	$_REQUEST["starts"],
				"ends"		=>	$_REQUEST["ends"],
				"type"		=>	$_REQUEST["type"],
				"starts"	=>	$_REQUEST["starts"],
			]);
			\MAPLE::DashMessage($ret);
			\URL::redirect(\URL::name("rubixcode/hotel/admin","rates"));
		} else {
			\MAPLE::DashMessage([
				"type"	=>	"error",
				"title"	=>	"Invalid Dates",
				"message"=>	"Please Select Dates Such that there is a minimum 15 Days Gap."
			]);
			\URL::redirect(\URL::name("rubixcode/hotel/admin","rates-add",$_REQUEST));
		}
	}

	public static function r_prices_edit(){
		$ret = RATES::season_edit([
			"starts"	=>	$_REQUEST["begin"],
			"type"		=>	$_REQUEST["type"]
		]);
		\MAPLE::DashMessage($ret);
		if($ret["type"] == "success") \URL::redirect(\URL::name("rubixcode/hotel/admin","rates"));
		else \URL::redirect(\URL::name("rubixcode/hotel/admin","rates-edit",["begin" => $_REQUEST["begin"]]));
	}

	public static function r_prices_delete(){
		$ret = RATES::season_delete([
			"starts"	=>	$_REQUEST["begin"],
		]);
		\MAPLE::DashMessage($ret);
		if($ret["type"] == "success") \URL::redirect(\URL::name("rubixcode/hotel/admin","rates"));
		else \URL::redirect(\URL::name("rubixcode/hotel/admin","rates-delete",["begin" => $_REQUEST["begin"]]));
	}

	public static function n_navbar_search(){
		if(\SECURITY::has_access("hotel-admin") && \URL::http("%CURRENT%") != \URL::name("rubixcode/hotel/admin","search"))
		\UI::navbar()->add_button("<i class='material-icons left'>search</i> Search",\URL::name("rubixcode/hotel/admin","search"));
	}
}

?>
