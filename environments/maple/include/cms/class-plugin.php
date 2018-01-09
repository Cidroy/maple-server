<?php
	require_once __DIR__.'/class-install.php';

	class CMS_PLUGIN extends MAPLE{

		protected static function edit($value=''){
			/**
			* TODO CREATE A HANDLER
			*/
		}

		protected static function multiple_permission_set($value=''){
			/**
			* TODO : Add multiple plugin permission handling here
			*/
		}

		public static function load_plugin_list(){
			foreach (FILE::get_folders(ROOT.PLG) as $k) {
				$json=ROOT.PLG."$k/package.json";
				if(file_exists($json)){
					$json = json_decode(file_get_contents($json),true);
					if(isset($json['Maple'])){
						$maple = $json['Maple'];
						self::$_plugin_list[$maple['ID']] = ROOT.PLG."$k";
					}
				}
			}
		}

		protected static function install($data){
			CACHE::delete("maple","active-plugin",["user-specific"=>true]);
			self::load_plugin_list();
			$permission = FILE::read(self::$_plugin_list[$data['plugins']]."/permissions.json");
			if(!$permission){
				echo TEMPLATE::Render('maple','admin-plugin-warning-no_permission',array(
					'name'	=>	json_decode(FILE::read(self::$_plugin_list[$data['plugins']]."/package.json"),true)['name'],
				));
			}else{
				$__permission_list	=	json_decode($permission,true)['permission'];
				$__user_types	= [];
				$__user_names 	= json_decode(FILE::read(ROOT.INC."config/user-type.json"),true);
				foreach ($__user_names as $key => $value) {
					$__user_types[$value]	=	[
						'name'	=>	$key,
						'permission' => []
					];
				}
				ksort($__user_types);

				/**
				 * Optimize
				 */
				foreach ($__permission_list as $name => $info) {
					$__parse	=	$info['default'];
					if($__parse == "*"){
						foreach ($__user_types as $key => $value) {
							$__user_types[$key]['permission'][$name] = [
								'access'	=>	true,
								'description'=>	$info['description'],
							];
						}
					} else {
						foreach ($__user_types as $key => $value) {
							$__user_types[$key]['permission'][$name] = [
								'access'	=>	false,
								'description'=>	$info['description'],
							];
						}
						$__parse = explode(',', $__parse);

						$limits = [];
						foreach ($__parse as $access) {
							$level	=	"";
							$additional	=	"";
							preg_match("/[a-zA-Z]+/", $access, $level);
							if(preg_match('/\+/', $access)){
								foreach ($__user_names as $key => $value) {
									if($value >= $__user_names[$level[0]]){
										$__user_types[$value]['permission'][$name] = [
											'access'	=>	true,
											'description'=>	$info['description'],
										];
									}
								}
							} else {
								$__user_types[$__user_names[$level[0]]]['permission'][$name] = [
									'access'	=>	true,
									'description'=>	$info['description'],
								];
							}
						}
					}
				}

				echo TEMPLATE::Render('maple','admin-plugin-activate-single',[
					'form'	=>	[
						'ajax'	=>	HTML::AjaxInput('maple','update-permission'),
						'permissions'	=>	$__user_types,
						'id'	=>	$data['plugins'],
					],
				]);
			}
		}

		public static function show_all_plugins(){
			if(URL::has_request(['edit'])){
				self::edit();
				return;
			}
			if (URL::has_request(['permission'])) {
				$data = SESSION::get_var('maple','plugin_change');
				if($data){
					if(is_array($data['plugins'])) self::multiple_permission_set();
					else{
						if($data['type'] == 'activate'){
							self::install($data);
							return;
						}
						else if($data['type'] == 'deactivate'){
						}
					}
				}
			}
			$data = array(
				'plugins'=>[],
				'maple' => [
					'root' => URL::http("%ROOT%"),
					'current' => URL::http("%CURRENT%")
			 	]
			);
			foreach (FILE::get_folders(ROOT.PLG) as $folder) {
				if(file_exists(ROOT.PLG."$folder/package.json")){
					$json = FILE::read(ROOT.PLG."$folder/package.json");
					$json = json_decode($json,true);
					array_push($data['plugins'],$json);
				}
			}
			echo TEMPLATE::Render('maple','plugin-card-min',$data);
		}

		protected static function activate($plugin){
			if($json = FILE::read(self::$_plugin_list[$plugin]."/package.json")){
				$json = json_decode($json,true);
				if(isset($json["Maple"]["Setup"] )) Maple\Cms\INSTALLER::Activate(array_merge([
					"Plugin"	=>	self::$_plugin_list[$plugin]
				],$json["Maple"]["Setup"]));
				if($json['Maple']['Active']) self::DashMessage([
					'owner' => 'maple',
					'message'=> "<strong>warning : </strong> Plugin <strong>$json[name]</strong> is already active",
					'type'	=> 'warning',
					'dismiss' => true
				]);
				else{
					$json['Maple']['Active'] = true;
					$json['Maple']['ID']	 = md5(time());
					$json['Maple']['Path']	 = URL::conceal_path(self::$_plugin_list[$_REQUEST['plugin']]);
					ENVIRONMENT::lock("maple/cms : activate-plugin");
					FILE::write(self::$_plugin_list[$_REQUEST['plugin']]."/package.json",json_encode($json,JSON_PRETTY_PRINT));
					ENVIRONMENT::unlock();
					self::DashMessage([
						'owner' => 'maple',
						'message'=> "Plugin <strong>$json[name]</strong> is now Active",
						'type'	=> 'success',
						'dismiss' => true
					]);
					SESSION::set_var('maple','plugin_change',[
						'type'	 =>	'activate',
						'plugins'=>	$json['Maple']['ID']
					]);
					$_REQUEST['redirect_to']	.= "?permission";
				}
			} else throw new Exception("Plugin not found", 1);

		}

		protected static function disable($plugin){
			if($json = FILE::read(self::$_plugin_list[$plugin]."/package.json")){
				$json = json_decode($json,true);
				if(isset($json["Maple"]["Setup"] )) Maple\Cms\INSTALLER::Deactivate(array_merge([
					"Plugin"	=>	self::$_plugin_list[$plugin]
				],$json["Maple"]["Setup"]));
				if(!$json['Maple']['Active']) {
					self::DashMessage([
						'owner' => 'maple',
						'message'=> "<strong>warning : </strong> Plugin <strong>$json[name]</strong> is already disabled",
						'type'	=> 'warning',
						'dismiss' => true
					]);
				}
				else{
					$json['Maple']['Active'] = false;
					ENVIRONMENT::lock("maple/cms : disable-plugin");
					FILE::write(self::$_plugin_list[$_REQUEST['plugin']]."/package.json",json_encode($json,JSON_PRETTY_PRINT));
					ENVIRONMENT::unlock();
					$pdata = [];
					$pfile = new _FILE(self::$_plugin_list[$plugin]."/permissions.json");
					if($pfile->exists()){
						$pdata = array_keys(json_decode($pfile->read(),true)["permission"]);
						$pdata = URL::http("%ROOT%",[
							"maple_ajax" 	=> "maple",
							"maple_ajax_action"	=>	"update-permission",
							"remove-access"	=>	"all",
							"permission"	=>	$pdata,
							"redirect_to"	=>	URL::name("maple/core","plugins")
						]);
					} else $pdata = URL::http("%ADMIN%");
					self::DashMessage([
						'owner' => 'maple',
						'message'=> "Plugin <strong>{$json["name"]}</strong> is now Deactivated",
						'type'	=> 'success',
						'dismiss' => true
					]);
					SESSION::set_var('maple','plugin_change',[
						'type'	 =>	'deactivate',
						'plugins'=>	$json['Maple']['ID']
					]);
					$_REQUEST['redirect_to'] = $pdata;
				}
			} else throw new Exception("Plugin not found", 1);
		}

		public static function plugin_single_edit(){
			$error = true;
			if(URL::has_request(array('action','plugin','redirect_to'),'post')){
				if(isset(self::$_plugin_list[$_REQUEST['plugin']]) && in_array($_REQUEST['action'],array('activate','disable','edit','delete'))){
					switch ($_REQUEST['action']) {
						case 'activate'	: self::activate($_REQUEST['plugin']);	break;
						case 'disable'	: self::disable($_REQUEST['plugin']);	break;
						case 'edit':
								$time = time();
								$token = md5($time);
								SESSION::set_var('token',$token,[
									'plugin'	=>	$_REQUEST['plugin'],
									'time'		=>	$time
								]);
								$_REQUEST['redirect_to']	.= "?edit=".$token;
							break;
						case 'delete':
							break;
						default:
							break;
					}
				}
			}
			MAPLE::clear_cache();
			URL::redirect($_REQUEST["redirect_to"]);
		}
	}
?>
