<?php
/**
* This is maple framework core Login class add all hooks to this class for login related data
* @package Maple Framework
*/
class LOGIN{
	public static $error;

	public static function add_navbar(){
		if(!MAPLE::is_loggedin() && SECURITY::has_access('register')) UI::navbar()->add_button('<i class="material-icons left">person_add</i>Sign Up', URL::name('login','sign-up'));
		if(!MAPLE::is_loggedin() && URL::http("%CURRENT%")!=URL::name("login","login") ) UI::navbar()->add_button('<i class="material-icons left">person_pin</i>Login', URL::name("login","login",["redirect_to"=>URL::http("%CURRENT%")]));
		if(MAPLE::is_loggedin()) UI::navbar()->add_button('<i class="material-icons left">perm_identity</i>'.MAPLE::UserDetail('NAME'),URL::name("user","profile"));
	}

	public static function r_login(){
		UI::title()->add('Login');
		UI::js()->add_src(URL::http(__DIR__."/assets/login.js"));
		echo TEMPLATE::Render("login/core","login-full-page",self::LoginForm());
	}

	public static function r_forgot_password(){
		echo TEMPLATE::Render('login/core','forgot-password',[
			"form"	=> "type='POST' action='".URL::http("%ROOT%")."' data-handle='login_forgot'",
			"error"	=> "",
		]);
	}

	public static function select_function(){
		if(isset($_REQUEST['do'])){
			switch ($_REQUEST['do']) {
				case 'logout': self::Logout(); break;
				case 'login': self::doLogin(); break;
				default: MAPLE::SetError('Login','403x02');
					break;
			}
		}
	}

	// filters 	login_success	do tasks after the framework has logged in
	// filters 	login_failed	do tasks after the framework has logged failed
	public static function doLogin(){
		$error = array(
			'type'=>'error',
			'notify'=> array(
							'caption' => 'Invalid credentials.',
							'content' => 'Please check your username and password.',
							'icon' 	  => 'warning'
							),
			);
		header("Content-Type:application/json");
		if(!URL::has_request(array('Username','Password'))){
			return json_encode($error);
		}
		$sql = DB::_()->select("users","*",[
			"AND"	=>	[
				"OR"	=>	[
					"Login"	=>	$_REQUEST["Username"],
					"Email" =>	$_REQUEST["Username"],
				],
				"Pass"	=>	md5($_REQUEST['Password'])
			]
		]);
		if(count($sql)!=0){
			$res = $sql[0];
			$_SESSION['LOGIN::USER'] = $res ;
			if(isset($_REQUEST['remember-me'])) SESSION::extend_life(86400*90);
			MAPLE::SetUser('LOGIN::USER');
			$outputt = MAPLE::do_filters('login_success');
			$_REQUEST["redirect_to"] = isset($_REQUEST["redirect_to"])?$_REQUEST["redirect_to"]:"";
			$path = $_REQUEST["redirect_to"] == ""?URL::http("%ROOT%"):$_REQUEST["redirect_to"];
			if(SECURITY::has_access('maple-dashboard') && $_REQUEST["redirect_to"] == "" ) $path = URL::http("%ADMIN%");
			echo json_encode([
				'type'	=>	'success',
				'login'	=>	'true',
				'maple'	=>	[
					'location'=>$path,
					'token'   => session_id(),
				],
				'client'=>	[
					'name'	=> MAPLE::UserDetail("NAME"),
					'id'	=> MAPLE::UserDetail("ID"),
				],
				'filter'=>	$outputt,
			]);
		}
		else{
			self::$error.="Invalid Username or Password! Please try again!";
			MAPLE::do_filters('login_failed');
			return json_encode($error);
		}
	}

	// filters logout_before 	do what you wanna before logging out{ may prevent logout if logic fails}
	public static function Logout(){
		if(SECURITY::verify_nonce()){
			MAPLE::do_filters('logout_before');
			SESSION::end();
			URL::redirect(URL::http("%ROOT%"));
			// TODO : Logout from other frameworks too!
		}
		else{
			MAPLE::SetError('Login','403x02');
			var_dump([
				"token_key"	=>	SECURITY::$token_key,
				"tokens"	=>	SECURITY::$tokens,
				"iv"	=>	SECURITY::$iv,
				"nonce_life"	=>	SECURITY::$nonce_life,
			]);
		}
	}

	public static function Form($name,$args=''){
		$data = '';
		switch ($name) {
			case 'form-args'  : $data = 'method="POST" action="'.URL::http("%ROOT%").'" data-handle="login"'; break;
			case 'username'	  : $data = 'name="Username" id="Username"';	break;
			case 'password'	  : $data = 'name="Password" id="Password"';	break;
			case 'remember-me': $data = 'name="remember-me" id="remember-me"';	break;
			case 'form'		  : $data = '
					<input type="text" style="display:none" name="redirect_to" value="{{ current }}">
					';
					break;
			default: Log::debug("LOGIN [Invalid Argument]",array('field-name'=>$name,'param'=>$param)); break;
		}
		return $data;
	}

	public static function LogoutUrl(){
		return URL::name("login","logout",[
			"nonce"		=> 	SECURITY::generate_nonce()
		]);
	}

	// filters login_message 	the message to be shown before login form
	// filters login_form 		the message to be shown before login button
	public static function LoginForm(){
		return [
			"form_args"	=>	self::Form('form-args'),
			"username"	=>	self::Form('username'),
			"password"	=>	self::Form('password'),
			"filters"	=>	MAPLE::do_filters('login_form'),
			"nonce"		=>	SECURITY::generate_nonce(),
			"ajax"		=>	HTML::AjaxInput("login","login"),
			"remember_me" =>self::Form('remember-me'),
			"forgot_password" => URL::name("login",'forgot-password'),
			"sign_up" 	=> URL::name("login",'sign-up'),
			"error"		=> MAPLE::do_filters('login_message').self::$error,
			"redirect_to"=> (isset($_REQUEST['redirect_to'])?$_REQUEST['redirect_to']:''),
		];
	}

	public static function change_user_password($value=''){
		$done = false;
		$location = URL::http("%ROOT%");
		if(URL::has_request(['password','con-password','prev-password']) && $_REQUEST['con-password'] && $_REQUEST['password'] ){
			if(MAPLE::is_loggedin()){
				$prev = md5($_REQUEST['prev-password']);
				$pass = md5($_REQUEST['password']); $client = MAPLE::UserDetail('ID');
				$count = DB::_()->count("users",[
					"ID"	=>	$client,
					"Pass"	=>	$prev
				]);
				if($count){
					$done = DB::_()->update("users",[ "Pass"	=>	$pass ],[ "ID"	=>	$client ]);
					SESSION::set_var('login','change_message','password-changed');
				}
			}
		}
		if(!$done){
			if(!MAPLE::is_loggedin()){
				SESSION::set_var('feedback','no_login_message','<p class="orange white-text padding20">You need to login to change your password</p>');
				$location = URL::name("login","login",["redirect_to"=>URL::http("%CURRENT%")]);
			}
			else if($_REQUEST['password']!=$_REQUEST['con-password']){
				SESSION::set_var('login','change_message','no-password-match');
				$location = URL::name('user','settings');
			}
			else{
				SESSION::set_var('login','change_message','something-wrong');
				$location = URL::name('user','settings');
			}
		}
		URL::redirect($location);
	}

	public static function change_user_data($value=''){
		$done = false;
		$location = URL::name('user','profile');
		$retVal = true;
		if(URL::has_request(array('address','phone'))&&$_REQUEST['address']!=''&&$_REQUEST['phone']!=''){
			if(MAPLE::is_loggedin()){
				$email = isset($_REQUEST['email'])?$_REQUEST['email']:"";
				if($email){
					$sql = DB::_()->count("users",[ "Email" => $email ]);
					$retVal = ( $sql==0 ) ? true : false ;
					SESSION::set_var('login','change_message','something-wrong');
				}
				if($retVal){
					$address = $_REQUEST['address'];
					$phone = $_REQUEST['phone'];
					$client = MAPLE::UserDetail('ID');
					$done = DB::_()->update("users",[
							"Phone"		=>	$phone,
							"Address"	=>	$address,
							"Email"		=>	$email
					],[	"ID"	=>	$client	]);
					$client = MAPLE::UserDetail('ID');
					$res = DB::_()->select("users","*",[ "ID" => $client ]);
					$_SESSION['LOGIN::USER'] = $res ;
					MAPLE::SetUser('LOGIN::USER');
					SESSION::set_var('login','change_message','address-changed');
				}
			}
		}
		if(!$done){
			if(!MAPLE::is_loggedin()){
				SESSION::set_var('feedback','no_login_message','<p class="orange white-text padding20">You need to login to change your password</p>');
				$location = URL::name("login","login",["redirect_to"=>URL::http("%CURRENT%")]);
			}
			else
				SESSION::set_var('login','change_message','empty-field');
		}
		URL::redirect($location);
	}

	public static function filter_handler($value=''){
		if(SESSION::get_var('login','change_message')){
			$file = "assets/".SESSION::get_var('login','change_message',true);
			return TEMPLATE::Render("login/core",$file,[]);
		};
		return false;
	}

	public static function shortcode($param){
		switch ($param) {
			case 'full-page':
					if(MAPLE::is_loggedin()){
						$path = SECURITY::has_access('maple-dashboard') ? URL::http("%ADMIN%") : URL::http("%ROOT%");
						URL::redirect($path);
						die();
					}
					self::add_navbar();
					UI::content()->add('LOGIN::select_function',URL::http("%CURRENT%"),10);
					if(MAPLE::is_loggedin()) USER::AddContentHooks();
				break;
		}
	}

	public static function Random_String($length, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}
?>
