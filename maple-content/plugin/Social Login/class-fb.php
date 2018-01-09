<?php
	namespace kundan;
	class FB{
		protected static $_config =  [];
		protected static $fb = null;
		protected static $accessToken = null;
		protected static $permissions = ['user_friends','email'];
		protected static $profile = [];

		public static function show_all(){
			echo \TEMPLATE::Render("kundan/social","view-fb-config",[
				"fb"	=>	self::$_config
			]);
		}

		public static function LoginLink(){
			$helper = self::$fb->getRedirectLoginHelper();
			if(!self::$accessToken){
				return $loginUrl = $helper->getLoginUrl(\URL::http("%ROOT%test.php"),self::$permissions);
			}
			else {
				\MAPLE::do_filters("social_login");
				return false;
			}
		}

		public static function UserDetails($param){
			if(is_array($param)){
				$param = implode(",",$param) ;
			}
			$profile = [];
			if(isset(self::$profile[$param])) return self::$profile[$param];
			try {
				$profile_request = self::$fb->get("/me?fields={$param}");
				$profile = $profile->getGraphNode()->asArray();
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
				\Log::warning('Graph returned an error: ' . $e->getMessage());
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
				\Log::warning('Graph returned an error: ' . $e->getMessage());
			}catch(Exception $e){
				\Log::warning($e);
			}

			self::$profile[$param] = $profile;
			return $profile;
		}

		public static function show_edit(){
			echo \TEMPLATE::Render("kundan/social","view-fb-config",[
				"edit_mode"	=>	"true",
				"fb"	=>	self::$_config,
				"form"	=>	\HTML::AjaxInput("kundan/social","fb-edit-app")
			]);
		}

		public static function ah_edit_app(){
			var_dump($_REQUEST);
			if(\URL::has_request(["name","id","namespace","secret","link"])){
				\MAPLE::DashMessage([
					"type"	=>	"success",
					"title"	=>	"Facebook login updated successfully"
				]);
				\FILE::write(__DIR__."/fb-config.json",json_encode([
					"name"		=> $_REQUEST["name"],
					"namespace"	=> $_REQUEST["namespace"],
					"id"		=> $_REQUEST["id"],
					"secret"	=> $_REQUEST["secret"],
					"default_graph_version"	=> isset($_REQUEST["default_graph_version"])?$_REQUEST["default_graph_version"]:"v2.8",
					"link"		=> $_REQUEST["link"]
				],JSON_PRETTY_PRINT));
				\URL::redirect(\URL::name("kundan/social/fb","admin-fb-info"));
			}
			else{
				\MAPLE::DashMessage([
					"type"	=>	"error",
					"title"	=>	"insufficient parameters"
				]);
				\URL::redirect(\URL::name("kundan/social/fb","admin-fb-edit"));
			}
		}

		public static function Initialize(){
			self::$_config = json_decode(\FILE::Read(__DIR__."/fb-config.json"),true);
			require_once(__DIR__."/Facebook/autoload.php");

			self::$fb = new \Facebook\Facebook([
			  'app_id' => self::$_config["id"],
			  'app_secret' => self::$_config["secret"],
			  'default_graph_version' => self::$_config["default_graph_version"],
			]);

			// Get access Token
			try {
				$helper = self::$fb->getRedirectLoginHelper();
				if (isset($_SESSION['facebook_access_token'])) {
					self::$accessToken = $_SESSION['facebook_access_token'];
				} else {
					self::$accessToken = $helper->getAccessToken();
				}
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
				\Log::warning('Graph returned an error: ' . $e->getMessage());
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
				\Log::warning('Graph returned an error: ' . $e->getMessage());
			}catch(Exception $e){
				\Log::warning($e);
			}


		}
	}

	FB::Initialize();
?>
