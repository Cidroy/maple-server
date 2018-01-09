<?php
/**
* This is meant to provide the Client side feasiblity
* @package Maple Framework
*/
class USER{
	public static $HERE = false;

	public static function r_profile(){
		if(MAPLE::is_loggedin()){
			UI::title()->add('Profile');
			UI::content()->add('USER::ShowProfile',[
				'author'	=> 'login/core',
				'template'	=> 'page-user-home'
			]);
		}
	}

	public static function r_profile_edit(){
		if(MAPLE::is_loggedin()){
			UI::content()->add('USER::ShowSettings',array(
				'author'	=> 'login',
				'template'	=> 'page-user-setting'
			));
		}
	}

	public static function AddContentHooks(){
		self::$HERE = dirname( __FILE__ );
	}

	public static function ShowSettings($value){
		UI::title()->add('Edit Profile');
		echo TEMPLATE::Render($value['author'],$value['template'],array(
				'form1'	=> array(
						'arguments'	=> '',
						'nonce'	=> HTML::AjaxInput('user','change_user_address'),
						'submit'	=> HTML::Button(array(
															'type'	=>'submit',
															'class'	=>'btn-floating right green white-text',
															'content'=>'<i class="material-icons">done</i>')
						),
						'name'		=> HTML::Input('name',array(
															'type'=>'text',
															'readonly'=>'',
															'value'=>MAPLE::UserDetail('NAME'),
															'id'=>'name')
						),
						'username'	=> HTML::Input('username',array(
															'type'=>'text',
															'readonly'=>'',
															'value'=>MAPLE::UserDetail('LOGIN'),
															'id'=>'username')
						),
						'password'	=> HTML::Input('password',array(
															'type'=>'password',
															'value'=>'',
															'id'=>'password')
						),
						'confirm_password'	=> HTML::Input('con-password',array(
															'type'=>'password',
															'value'=>'',
															'id'=>'con-password')
						),
						'previous_password'	=> HTML::Input('prev-password',array(
															'type'=>'password',
															'value'=>'',
															'id'=>'prev-password')
						),
					),
				'form2'	=> array(
						'arguments'	=> '',
						'nonce'	=> HTML::AjaxInput('user','change_user_password'),
						'submit'	=> HTML::Button(array(
															'type'	=>'submit',
															'class'	=>'btn-floating right green white-text',
															'content'=>'<i class="material-icons">done</i>')
						),
						'email' => HTML::Input('email',array(
															'type'=>'email',
															'validate'=>'',
															'value'=>MAPLE::UserDetail('EMAIL'),
															'id'=>'email')
						),
						'phone'	=> HTML::Input('phone',array(
															'type'=>'text',
															'validate'=>'',
															'value'=>MAPLE::UserDetail('PHONE'),
															'id'=>'phone')
						),
						'address'	=> HTML::Textbox('address',array(
															'class'=>"materialize-textarea",
															'required'=>'',
															'validate'=>'',
															'content'=>MAPLE::UserDetail('ADDRESS'),
															'id'=>'username',
															'name'=>'address')
						),
					),
				'maple'	=> array(
						'url'	=> array(
								'root'	=> URL::http("%ROOT")
							)
					),
				'login'	=> array(
						'url'	=> array(
							'profile'	=> URL::name('user','profile')
						)
					),
				'filter'=> array(
						'user_setting_content'	=> MAPLE::do_filters('user_setting_content')
					)
			));
	}

	public static function ShowProfile($value){
		echo TEMPLATE::Render($value['author'],$value['template'],array(
				'user'	=> array(
						'name'		=> HTML::Input('name',array(
															'type'=>'text',
															'readonly'=>'',
															'value'=>MAPLE::UserDetail('NAME'),
															'id'=>'name')
						),
						'username'	=> HTML::Input('username',array(
															'type'=>'text',
															'readonly'=>'',
															'value'=>MAPLE::UserDetail('LOGIN'),
															'id'=>'username')
						),
						'email'		=> HTML::Input('email',array(
															'type'=>'email',
															'readonly'=>'',
															'value'=>MAPLE::UserDetail('EMAIL'),
															'id'=>'email')
						),
						'phone'		=> HTML::Input('phone',array(
															'type'=>'text',
															'readonly'=>'',
															'value'=>MAPLE::UserDetail('PHONE'),
															'id'=>'phone')
						),
						'address'	=>  HTML::Textbox('address',array(
															'class'=>"materialize-textarea",
															'required'=>'',
															'readonly'=>'',
															'content'=>MAPLE::UserDetail('ADDRESS'),
															'id'=>'address')
						),
					),
				'login'	=> array(
						'url'	=> [
							'settings'	=> URL::name('user','settings'),
							"logout"	=> LOGIN::LogoutUrl()
						]
					),
				'filter'=> [
					'filter'	=> MAPLE::do_filters('page_user_home'),
				]
			)
		);
	}

	public static function getAddress($element='all'){
		switch ($element) {
			case 'pincode':	return '400500';
				break;
			default:
				break;
		}
	}

	public static function h_details(){
		$res = [
			"username"	=>	MAPLE::UserDetail("LOGIN"),
			"id"	=>	MAPLE::UserDetail("ID"),
			"name"	=>	MAPLE::UserDetail("NAME"),
			"email"	=>	MAPLE::UserDetail("EMAIL"),
			"gender"	=>	MAPLE::UserDetail("GENDER"),
			"age"	=>	MAPLE::UserDetail("AGE"),
		];
		echo json_encode($res,JSON_PRETTY_PRINT);
	}
}
?>
