<?php
/**
* This class is meant to provide functionality to the Administrator regarding the user
* @package Maple Framework
*/
class AUSER{
	// filter : admin_user_view show all data that is particular to the admin with the specified client
	public static function show_all_users($value=''){
		$show = true;
		if(isset($_REQUEST['id'])){
			$sql = DB::_()->select("users","*",[ "ID"	=>	$_REQUEST["id"] ]);
			if(count($sql)){
				$show = false;
				$sql = $sql[0];
				echo TEMPLATE::Render('login/core','user-detail',[
					'user'	=>	[
						'name'		=>	$sql['Name'],
						'username'	=>	$sql['Login'],
						'email'		=>	$sql['Email'],
						'phone'		=>	$sql['Phone'],
						'address'	=>	$sql['Address'],
						'url'		=>	[
							'edit'	=>	URL::name('user','edit',['edit'=>$sql['ID']]),
							'delete'=>	URL::name('user','delete',["user" => $sql['ID']]),
						],
					],
					'filter'	=>	MAPLE::do_filters('admin_user_view'),
				]);
			}
			else{
				// TODO : Use template
				echo '<div class="card horizontal"><div class="card-stacked"><div class="card-content">';
				echo "<span class='card-title red-text'><i class='material-icons left'>warning</i>No User Found</span>";
				echo "</div></div></div>";
			}
		}
		if($show){
			echo MAPLE::do_filters('admin_register_new');
			$sql = DB::_()->select("users","*");
			// TODO : Use template
			echo "<ul class='collection z-depth-1 with-header'><li class='collection-header'><h5>All Users</h5></li>";
			foreach ($sql as $row) {
				echo  '<li class="collection-item avatar" data-maple-link href="?id='.$row['ID'].'">
							<i class="material-icons circle red">perm_identity</i>
							<span class="title">'.$row['Name'].'</span>
							<p>'.$row['Login'].'<br>
							'.$row['Phone'].'
							</p>
						</li>';
			}
			echo "</ul>";
		}
	}

	// filter admin_register_new Message for new register filter
	public static function add_new_user(){
		UI::title()->add('Register');
		UI::js()->add_src(URL::http(__DIR__."/assets/login.js"));
		$types = json_decode(FILE::read(URL::dir("%INCLUDE%config/user-type.json")),true);
		$user_types = [];
		foreach ($types as $key => $value) {
			$user_types[] = [
				'id'	=>	$value,
				'name'	=>	$key,
			];
		}
		echo TEMPLATE::Render('login/core','add-user',[
			'maple'	=>	[
				'url'	=>	[
					'root'	=>	URL::http("%ROOT%"),
				],
			],
			'filter'=>	[
				'admin_register_new'	=> MAPLE::do_filters('admin_register_new'),
			],
			'form'	=>	[
				'ajax'	=>	HTML::AjaxInput("auser","insert_new_user"),
				'utypes'=>	$user_types,
			],
		]);
	}

	public static function insert_user(){
		$location = URL::name('user','add-new');
		if($_REQUEST['password']!=$_REQUEST['con-password']) {
			SESSION::set_var('auser','admin_register_new','<h5>Password and Confirm-Password did not match</h5>');
		}
		$sql = DB::_()->select("users","*",[
			"OR"	=>	[
				"Login"	=>	$_REQUEST["username"],
				"Email"	=>	$_REQUEST["email"],
			]
		]);
		foreach ($sql as $row) {
			if($row['COUNT(*)']=='0'){
				$activation = md5(time());
				$password   = md5($_REQUEST['password']);
				$access 	= isset($_REQUEST['user-type']) && SECURITY::has_access('login-custom-type') ? $_REQUEST['user-type'] : 0 ;
				$sql2 = DB::_()->insert("users",[
					"Login"	=>	$_REQUEST["username"],
					"Pass"	=>	$password,
					"Name"	=>	$_REQUEST["name"],
					"Email"	=>	$_REQUEST["email"],
					"Activation_Key"	=>	$activation,
					"Status"	=>	$_REQUEST["status"]?$_REQUEST["status"]:0,
					"Phone"		=>	$_REQUEST["phone"],
					"Address"	=>	$_REQUEST["address"],
					"(JSON)Permission"=>[
						"set"	=>	[],
						"unset"	=>	[]
					],
				]);
				SESSION::set_var('auser','admin_register_new','<h5>User Added Successfully</h5>');
				// TODO : Use Mail template
				MAIL::Send($_REQUEST['email'],"Your Account verification Link.",
					str_replace(array("{{activation-key}}",'{{username}}',"{{name}}"),array($activation,$_REQUEST['username'],$_REQUEST['name']),FILE::read(__DIR__."/assets/new-registeration-mail.html"))
					);
				MAPLE::do_filters('register_success');
				break;
			}
			else{
				if($row['Login']==$_REQUEST['username'])
					SESSION::set_var('auser','admin_register_new','<h5>Username unavailable</h5>');
				if($row['Email']==$_REQUEST['email'])
					SESSION::set_var('auser','admin_register_new','<h5>Email already registered!</h5>');
			}
		}
		URL::redirect($location);
	}

	public static function filter_handler(){
		return SESSION::get_var('auser','admin_register_new')?SESSION::get_var('auser','admin_register_new',true):false;
	}

	//filter delete_user param => user = ID do the data that is required after user is deleted
	public static function delete_user(){
		$sql = DB::_()->select("users","*",[ "ID" => $_REQUEST["user"] ]);
		if(count($sql)){
			AUSER::Trash($_REQUEST['user'],'login',$sql);
			$sql = DB::_()->delete("users",[ "ID" => $_REQUEST["user"] ]);
			if($sql){
				MAPLE::do_filters("delete_user",[ "ID" => $_REQUEST["user"]]);
				SESSION::set_var("auser","admin_register_new","<h5>User Deleted!</h5>");
			}
			else SESSION::set_var("auser","admin_register_new","<h5>Invalid User ID!</h5>");
		}
		else SESSION::set_var("auser","admin_register_new","<h5>Invalid User ID!</h5>");
		URL::redirect(URL::name("user","all-users"));
	}

	public static function Trash($id,$author,$data){
		$path = ROOT.DATA."trash/User-$id.json";
		$content = FILE::read($path);
		$content = $content?json_decode($content,true):array();
		$content[$author] = $data;
		FILE::append($path,json_encode($content));
	}

	public static function dashboard_new_users(){
		echo '<span class="card-title">New Users</span>';
		echo "<br>TODO";
	}

	public static function registration_settings(){
		echo TEMPLATE::Render('login/core','user-settings',array(
					'maple' => array(
							'root' => URL::http("%ROOT%"),
							'current' => URL::http("%CURRENT%"),
						),
					'tab1' => array(
							'table'=> array(
								'types' => array(
										array('name'=>'SuperUser','core'=>true),
										array('name'=>'Administrator'),
										array('name'=>'Guest','core'=>true),
									)
								),
							'form' => array(
									'input1_name' => array(
											'name' => 'type_name'
										),
									'ajax_action' => HTML::AjaxInput('auser','insert_user_group'),
									'higher_select' => array(
											'name' => 'higher_access_level',
											// TODO : Make this input dynamic
											'options' => array(
													array('name'=>'SuperUser','value'=>'3000'),
													array('name'=>'Administrator','value'=>'2500'),
													array('name'=>'Client','value'=>'1000'),
												)
										),
									'lower_select' => array(
											'name' => 'higher_access_level',
											// TODO : Make this input dynamic
											'options' => array(
												)
										)
								)
						),
					'tab2' => array(
							'plugins' => array(
								// TODO : Dynamic
									array(
										'name' => 'A Plugin',
										'dashboard' => array(
												array('name'=>'dash1','description'=>'lenthy'),
												array('name'=>'dash1','description'=>'lenthy'),
												array('name'=>'dash1','description'=>'lenthy'),
											)
										)
								)
						)
			));
	}
}
?>
