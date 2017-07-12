<?php
namespace rubixcode\hotel;

class CLIENT{

	public static function get_details($param){
		if(!isset($param["id"])) return false;
		$client = [];
		$z1 = \DB::_()->select("users",[
			"[>]hr_clients"	=>	["ID" =>	"id"]
		],[
			"users.ID(id)",
			"users.Name(name)",
			"users.Email(email)",
			"users.Login(login)",
			"users.Gender(gender)",
			"users.Age(age)",
			"users.Phone(phone)",
			"users.Address(address)",
			"hr_clients.city",
			"hr_clients.state",
			"hr_clients.zip",
			"hr_clients.passport",
		],[
			"users.ID"	=>	$param["id"]
		]);
		if(!$z1) return false;
		$z1 = $z1[0];
		$z3 = [
			"url"	=>	[
				"detail"	=>	\URL::name("rubixcode/hotel/admin","client-detail",["id"	=>	$param["id"]]),
				"edit"		=>	\URL::name("rubixcode/hotel/admin","client-edit",["id"	=>	$param["id"]]),
			]
		];
		return array_merge($z1,$z3);
	}

	public static function add_new($param){
		if(!\SECURITY::has_access("login-add-users")) return [
			"type"	=>	"error",
			"title"	=>	"invalid operation",
			"message"=>	"you do not have access to add new Client"
		];
		$client = \DB::_()->select("users","*",["Email" => $param["email"]]);
		if($client){
			$client = $client[0];
			return [
				"type"	=>	"warning",
				"title"	=>	"User Already Exists and was not Added",
				"message"=>	"The user <a href='".\URL::name("rubixcode/hotel/admin","client-detail",["id"	=>	$client["ID"]])."'>{$client["Name"]}</a> was Used as it was already registered with E-Mail Id : {$client["Email"]} ",
				"id"	=>	$client["ID"]
			];
		}
		$param["username"] = base64_encode(time());
		$password = md5(\LOGIN::Random_String(10));
		$param["status"] = \MAPLE::UserType("Subscriber");
		$sql = \DB::_()->insert("users",[
			"Login"		=>	$param["username"],
			"Pass"		=>	$password,
			"Name"		=>	$param["name"],
			"Email"		=>	$param["email"],
			"Status"	=>	$param["status"]?$param["status"]:0,
			"Phone"		=>	$param["phone"],
			"Address"	=>	$param["address"],
			"(JSON)Permission"=>[
				"set"	=>	[],
				"unset"	=>	[]
			],
		]);
		$id = \DB::_()->id();
		$sql = \DB::_()->insert("hr_clients",[
			"id"	=> $id,
			"city"	=>	$param["city"],
			"state"	=>	$param["state"],
			"zip"	=>	$param["zip"],
			"passport"=>$param["passport"],
		]);
		\MAIL::Send($param["email"],"Credentials",\TEMPLATE::Render("rubixcode/hotel","client/mail-new-registration",[
			"username"	=>	$param["email"],
			"password"	=>	$password,
		]));
		return [
			"type"	=>	"success",
			"title"	=>	"New Client Added!",
			"message"=>	"Username : <a href='".\URL::name("rubixcode/hotel/admin","client-detail",["id" => $id])."'>{$param["email"]}</a><br><!--Password : {$password}-->",
			"id"	=>	$id
		];
	}

	public static function exists($param){
		if(!isset($param["id"])) return false;
		return \DB::_()->count("users",["ID"	=>	$param["id"]]) ? true : false ;
	}

	public static function all(){
		return \DB::_()->select("users",[
			"[>]hr_clients" =>	["ID" => "id"]
		],[
			"users.ID(id)",
			"users.Name(name)",
			"users.Email(email)",
			"users.Login(login)",
			"users.Gender(gender)",
			"users.Age(age)",
			"users.Phone(phone)",
			"users.Address(address)",
			"hr_clients.city",
			"hr_clients.state",
			"hr_clients.zip",
			"hr_clients.passport",
		],[]);
	}

	public static function statistics(){
		return \DB::_()->count("users","ID");
	}
}

?>
