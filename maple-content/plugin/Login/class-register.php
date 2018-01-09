<?php
/**
 * This is a sign in class that helps uer to register.
 * @subpackage Login
 * @package Maple Framework
 */
class SIGN{

	//filters 	register_form 	=> contents at the end of registration form
	public static function r_sign_page() {
		UI::title()->add('Register');
		UI::js()->add_src(URL::http(__DIR__."/assets/login.js"));
		UI::content()->add('','');
		echo TEMPLATE::Render("login/core","assets/sign-up",[
			"form_args" 	=> "type='POST' action='".URL::http("%ROOT%")."' data-handle='sign-up'",
			"name" 			=> HTML::Input('name',array( "type" => "text", "required"=>"" ,"validate"=>"","id"=>"name")),
			"username" 		=> HTML::Input('username',array( "type" => "text", "required"=>"" ,"validate"=>"","id"=>"username")),
			"password" 		=> HTML::Input('password',array( "type" => "password", "required"=>"","id"=>"password")),
			"confirm_password" => HTML::Input('con-password',array( "type" => "password", "required"=>"","id"=>"con-password")),
			"email" 		=> HTML::Input('email',array( "type" => "email","id"=>"email")),
			"phone" 		=> HTML::Input('phone',array( "type" => "text", "required"=>"" ,"validate"=>"","id"=>"phone")),
			"address" 		=> HTML::Textbox('address',array( "class" => "materialize-textarea", "required"=>"" ,"validate"=>"",'name'=>'address',"id"=>"address")),
			"nonce" 		=> HTML::Input('nonce',array( "type" => "text", "value"=> SECURITY::generate_nonce(),"hidden"=>'')),
			"ajax" 			=> HTML::AjaxInput("login","login-register"),
			"submit" 		=> MAPLE::do_filters('register_form').HTML::Input('submit',array("type"=>"submit","class"=>"btn red darken-2","style"=>"display:block;margin:auto;"))
		]);
	}

	//filters 	register_success 	=> after successfull registeration but before redirection.
	public static function register(){
		$error = array(
			'type'=>'error',
			'notify'=> array(
							'caption' => 'Internal error',
							'content' => 'We are trying to fix it. please have patience.',
							'icon' 	  => 'warning'
							),
			'maple'=>array(
					'addclass' => array(
							'class' => 'error',
							'selector' => '[name={{object}}]',
							'object' => array()
						)
				)
			);
		if(!URL::has_request(array('name','username','password','con-password','phone','address'))){
			return json_encode(array(
				'type'=>'error',
				'notify'=> array(
							'caption' => 'Incomplete details',
							'content' => 'Your details are Incomplete',
							'icon' 	  => 'warning'
							),
				));
		}
		if($_REQUEST['password']!=$_REQUEST['con-password']) {
			$error['notify']['caption'] = "Passwords did not match.";
			$error['notify']['content'] = "Your confirmation password did not match.";
			array_push($error['maple']['addclass']['object'] ,'password');
			array_push($error['maple']['addclass']['object'] ,'con-password');
			return json_encode($error);
		}
		$sql = DB::_()->select("users","*",[
			"OR"	=>	[
				"Login"	=>	$_REQUEST["username"],
				"Email"	=>	$_REQUEST["email"]
			]
		]);
		if(count($sql)){
			$activation = md5(time());
			$password   = md5($_REQUEST['password']);
			$sql2 = DB::_()->insert("users",[
				"Login"	=>	$_REQUEST["username"],
				"Pass"	=>	$password,
				"Name"	=>	$_REQUEST["name"],
				"Email"	=>	$_REQUEST["email"],
				"Activation_Key"	=>	$activation,
				"Status"	=>	0,
				"Phone"		=>	$_REQUEST["phone"],
				"Address"	=>	$_REQUEST["address"],
				"(JSON)Permission"=>[
					"set"	=>	[],
					"unset"	=>	[]
				],
			]);
			if(!$sql2) die(json_encode($error));
			$error = [
				'type' => 'success',
				'maple' => ['location'=>URL::name("login","login",["redirect_to"=>URL::http("%CURRENT%")])."?newly-registerd"],
			];
			// TODO : Change verification link
			MAIL::Send($_REQUEST['email'],"Your Account verification Link.",
				str_replace(array("{{activation-key}}",'{{username}}',"{{name}}"),array($activation,$_REQUEST['username'],$_REQUEST['name']),FILE::read(__DIR__."/assets/new-registeration-mail.html"))
				);
			MAPLE::do_filters('register_success');
			return json_encode($error);
		}
		else{
			foreach ($sql as $row) {
				if($row['Login']==$_REQUEST['username']){
					$error['notify']['caption'] = "Username unavailable";
					$error['notify']['content'] = "Please try another username.";
					array_push($error['maple']['addclass']['object'],'username');
				}
				if($row['Email']==$_REQUEST['email']){
					$error['notify']['caption'] = "Email already registered!";
					$error['notify']['content'] = "It seems that you have already registered, please check your E-Mail or try <a href='".URL::name('login','forgot-password')."' style='text-decoration:none; font-weight:bold; color:#FFF;'>forgot password</a>";
					array_push($error['maple']['addclass']['object'],'email');
				}
			}
		}

		return json_encode($error);
	}

	public static function filter_login_message(){
		$msg = '';
		if(isset($_REQUEST['newly-registerd'])) $msg = "<h4 class='green-text'>Thanks for registering with us!</h4>";
		return $msg;
	}
}
?>
