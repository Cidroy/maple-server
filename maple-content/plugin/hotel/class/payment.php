<?php
namespace rubixcode\hotel;

class PAYMENT{

	public static function html($param){
		$param = array_merge([
			"amount"	=>	0.00
		],$param);
		return \TEMPLATE::Render("rubixcode/hotel","payment/card",[
			"currency"	=>	[
				"symbol"	=>	"Rs."
			],
			"payment"	=>	[
				"amount"	=>	$param["amount"]
			]
		]);
	}

	public static function get_transation_from_request(){
		if(!isset($_REQUEST["transaction"])) {
			\MAPLE::DashMessage([
				"type"	=>	"debug",
				"message"=>	"Transaction details missing",
			]);
			return false;
		}
		if(!isset($_REQUEST["transaction"]["amount"])){
			\MAPLE::DashMessage([
				"type"	=>	"debug",
				"message"=>	"Amount not provided!"
			]);
			return false;
		}
		if(!is_array($_REQUEST["transaction"]) ) {
			\MAPLE::DashMessage([
				"type"	=>	"debug",
				"message"=>	"Invalid transaction format",
			]);
			return false;
		}
		$transaction = $_REQUEST["transaction"];
		switch ($_REQUEST["transaction"]["method"]) {
			case 'card':
						if(!in_array($transaction["card-type"],["credit","debit"]))
							{
								\MAPLE::DashMessage([
									"type"	=>	"debug",
									"message"=>	"Invalid Card type",
								]);
								return false;
							}
						if(
							!isset($transaction["holder"]) ||
							!isset($transaction["number"]) ||
							!isset($transaction["expiry"]) ||
							!isset($transaction["cvv"])
						) {
							\MAPLE::DashMessage([
								"type"	=>	"debug",
								"message"=>	"insufficient card details",
							]);
							return false;
						}
						\DB::_()->insert("hr_payments",[
							"amount"	=>	$transaction["amount"],
							"type"		=>	$transaction["method"],
							"#time"		=>	"NOW()",
							"meta"		=>	json_encode([
								"holder"	=>	$transaction["card"]["holder"],
								"card"		=>	$transaction["card"]["card"],
								"card-type"		=>	$transaction["card"]["card-type"],
							]),
						]);
						return \DB::_()->id();
				break;
			case 'cod':
						\DB::_()->insert("hr_payments",[
							"amount"	=>	$transaction["amount"],
							"type"		=>	$transaction["method"],
							"#time"		=>	"NOW()",
							"meta"		=>	json_encode([]),
						]);
						return \DB::_()->id();
				break;
			default : {
				\MAPLE::DashMessage([
					"type"	=>	"debug",
					"message"=>	"Invalid Payment Method",
				]);
				return false;
			}
		}
	}

	public static function details($param){
		$res = \DB::_()->select("hr_payments",[
			"amount",
			"time",
			"type(method)",
			"meta(details)"
		],["id"	=>	$param["id"]]);
		if(!$res) return false;
		else $res = $res[0];
		$res["details"] = json_decode($res["details"]);
		return $res;
	}

	public static function statistics(){
		return [
			"amount"	=>	\DB::_()->sum("hr_payments","amount"),
			"transactions"	=>	\DB::_()->count("hr_payments","amount"),
		];
	}

}

?>
