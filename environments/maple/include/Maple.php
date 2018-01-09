<?php
/**
* The Main Maple Framework class for the system to work
* Please use this class functions for api project
* @package Maple Framework
*/
/*
TODO : move functions to class CMS
 */
class MAPLE{

	private static $_HOOKS		= [];
	private static $_FILTERS	= [];
	private static $_OPTION		= [];
	private static $_ERRORS		= [];
	private static $_somethings	= [];
	private static $__is_admin	= false;
	private static $_AUTOLOAD	= [];
	private static $_SEARCHERS  = [];
	private static $_PLUGINS	= [];
	private static $_DO			= [];
	private static $__plugin_ready = false;
	private static $shortcodes	= [];
	private static $_ROUTES 	= [];
	private static $_TEMPLATES 	= [];

	public static $_USER		= [];
	public static $content		= false;

	protected static $_plugin_list= [];

	public static function GetOption($key)	{ return isset(self::$_OPTION[$key])?self::$_OPTION[$key]:false;	}

	/**
	 * FIXME : add_hook is not a solution
	 * [AddHook description]
	 * @param [type] $page [description]
	 */
	public static function AddHook($page)	{	array_push(self::$_HOOKS,$page);	}
	public static function ResolveHooks(){
		foreach (self::$_HOOKS as $page) {
			echo($page);
		}
	}

	private $__page_i = 0;

	public static $Response_Type='JSON';

	/**
	 * [Do_Autos description]
	 * TODO : add functionality to have parameterized input
	 */
	public static function Do_Autos(){
		foreach (self::$_DO as $func)
			call_user_func($func);
	}

	public static function SetUser($id=false){
		if(!$id&&isset($_SESSION['USER'])){self::$_USER = $_SESSION['USER'];}
		else if(isset($_SESSION[$id])){
			$res = $_SESSION[$id];
			SESSION::pause();
			SESSION::start(864000);
			foreach ($res as $key => $value) { self::$_USER[strtoupper($key)] = $value;}
			self::$_USER['LOG'] = true;
			unset($_SESSION[$id]);
			$_SESSION["USER"] = self::$_USER;
		}
		if(!self::is_loggedin()){
			self::$_USER['STATUS'] = json_decode(FILE::read(__DIR__."/config/user-type.json"),true)['Default'];
		}
	}

	public static function SetError($src,$type,$msg=''){ array_push(self::$_ERRORS,array($src,$type."\n\t".$msg)); }
	public static function Display_Error(){
		if(!empty(self::$_ERRORS)){
			echo "<h3>The Following errors have occured!</h3>";
			foreach (self::$_ERRORS as $e) {
				echo "<strong>$e[0] : </strong>$e[1]<br>";
			}
		}
	}

	/**
	 * TODO : Get Environment option and store for result
	 */
	public static function Environment($value){
		return 'windows';
	}

	public static function SanitizeOutput($content){
		return htmlspecialchars($content, ENT_QUOTES , 'UTF-8' );
	}

	public static function UserDetail($args){
		if(!self::$_USER) self::SetUser();
		return isset(self::$_USER[$args])?self::$_USER[$args]:false;
	}

	public static function GetPage($name){
		$x = DB::_()->select("pages",["Content"],[
			"name"	=>	"{$name}",
		]);
		foreach ($x as $row ) { return URL::dir($row['Content']); }
		return false;
	}

	public static function is_admin(){ return isset(self::$_USER['STATUS'])&&self::$_USER['STATUS']==self::UserType('Administrator')?true:false; }

	public static function is_loggedin(){ return isset(self::$_USER['LOG'])?true:false;}

	/**
	 * TODO : add functionality to get the datatype for response and act accordingly
	 * Detect wether an Ajax request was made and append a response to it
	 * @return boolean true if detected 'maple_ajax' and 'maple_ajax_action'
	 */
	public static function is_ajax_request(){
		return !isset(self::$_somethings['_ajax'])?(self::$_somethings['_ajax'] = isset($_REQUEST['maple_ajax'])&&isset($_REQUEST['maple_ajax_action'])?true : false) : self::$_somethings['_ajax'];
	}

	public static function Autoload($name){
		FILE::safe_require_once(
			isset(self::$_AUTOLOAD[$name])?self::$_AUTOLOAD[$name]:Log::error("$name Autoload not specified"),
			false,false
		);
	}

	public static function add_autoloader($class_name,$location){
	 isset(self::$_AUTOLOAD[$class_name])?
	 		(self::$_AUTOLOAD[$class_name]!=$location?
	 			Log::warning("Unable to Load : $class_name (Already Exists in $location)")
	 			:false
	 		)
	 		:self::$_AUTOLOAD[$class_name]=$location;
	}

	public static function is_admin_page($val=false){
		if($val) self::$__is_admin=true;
		else return self::$__is_admin;
	}

	/**
	 * TODO : make this dynamic
	 * Returns the value of user type for evaluation
	 * @param string $type return value of user type
	 */
	public static function UserType($type){
		static $_TYPES = false;
		if(!$_TYPES){
			$_TYPES = json_decode(FILE::read(__DIR__."/config/user-type.json"),true);
		}
		if(isset($_TYPES[$type])) return $_TYPES[$type];
		else return $_TYPES['Default'];
	}

	public static function clear_cache(){
		CACHE::delete("maple","active-plugin",["user-specific"=>true]);
		CACHE::delete("maple","active-ajax",["user-specific"=>true]);
		CACHE::delete("maple","active-admin-plugin",["user-specific"=>true]);
	}

	public static function clear_cache_ui(){
		echo '<ul class="collapsible" data-collapsible="accordion">
		    <li>
		      <div class="collapsible-header active">Active Plugin</div>
		      <div class="collapsible-body">';
	  	var_dump(CACHE::get("maple","active-plugin",[],["user-specific"=>true]));
		echo '</div>
		    </li>
		    <li>
		      <div class="collapsible-header">Active Ajax</div>
		      <div class="collapsible-body">';
		var_dump(CACHE::get("maple","active-ajax",[],["user-specific"=>true]));
		echo '</div>
		    </li>
		    <li>
		      <div class="collapsible-header">Active Admin Plugin</div>
		      <div class="collapsible-body">';
		var_dump(CACHE::get("maple","active-admin-plugin",[],["user-specific"=>true]));
		echo '</div>
		    </li>
		    <li>
		      <div class="collapsible-header">Security Permissions</div>
		      <div class="collapsible-body">';
		var_dump(SECURITY::get_permissions());
		echo '</div>
		    </li>
		  </ul>';

		self::clear_cache();
	}

	/**
	 * TODO :
	 * 		use a single directory file to keep track of all the plugins
	 *		create package manager class to keep track of package control
	 *		reduce the if else criteria by using array_merge
	 */
	 public static function Initialize(){
 		if(!self::$__plugin_ready){
 			if(CACHE::has("maple","active-plugin",[ "user-specific" => true ]) && BUFFER ){
				// TODO : clean at log out
 				$data = CACHE::get("maple","active-plugin",null,["user-specific"=>true]);
 				self::$_FILTERS		= $data["_FILTERS"] 	;
 				self::$_AUTOLOAD	= $data["_AUTOLOAD"] 	;
 				self::$_OPTION		= $data["_OPTION"] 		;
 				self::$_SEARCHERS	= $data["_SEARCHERS"]   ;
 				self::$_PLUGINS		= $data["_PLUGINS"] 	;
 				self::$_DO			= $data["_DO"] 			;
 				self::$shortcodes	= $data["shortcodes"] 	;
 				self::$_plugin_list	= $data["_plugin_list"] ;
 				self::$_ROUTES		= $data["_ROUTES"]  	;
 				self::$_TEMPLATES	= $data["_TEMPLATES"]  	;
 				foreach (self::$_ROUTES as $route) ROUTE::UseFile($route);
 				Log::info("Plugins Loaded from cache");
 			}
 			else{
				$query = DB::_()->select("options",["Name","Value"]);
 				foreach($query as $row)	{
 					self::$_OPTION[$row['Name']]=$row['Value'];
 				}
 				$plugin_template = [
 					"Id"		=>	0,
 					"Active"	=>	false,
 					"Filter"	=>	[],
 					"Autoload"	=>	[],
 					"app-route"	=>	[],
 					"Client"	=>	[
 						"Search"	=>	false,
 						"Do"		=>	false,
 						"Plugin"	=>	[]
 					],
 					"Shortcode"	=>	[],
 				];
 				$i = 0;
 				$j = 0;
 				foreach (FILE::get_folders(ROOT.PLG) as $k){
 					$i++;
 					$json=ROOT.PLG."$k/package.json";
 					if(file_exists($json)){
 						$json = json_decode(file_get_contents($json),true);
 						if(isset($json['Maple'])){
 							$maple = $json['Maple'];
 							$maple = array_merge($plugin_template,$maple);
 							self::$_plugin_list[$maple['ID']] = ROOT.PLG."$k";
 							if(!$maple['Active']) continue;
 							$j++;
 							foreach ($maple['Filter'] as $key => $value){
 								if(!isset(self::$_FILTERS[$key]))
 								self::$_FILTERS[$key] = [];
 								array_push(self::$_FILTERS[$key],$value);
 							}
 							foreach($maple['Autoload'] as $c=>$l) self::$_AUTOLOAD[$c] = ROOT.PLG.$k.'/'.$l;
 							if(is_array($maple["app-route"])){
 								foreach ($maple["app-route"] as $route) {
 									array_push(self::$_ROUTES,self::$_plugin_list[$maple['ID']]."/".$route);
 								}
 							}
 							else array_push(self::$_ROUTES,self::$_plugin_list[$maple['ID']]."/".$maple["app-route"]);
 							$maple['Client'] = array_merge($plugin_template['Client'],$maple['Client']);
 							array_push(self::$_SEARCHERS,$maple['Client']['Search']);
 							array_push(self::$_DO,$maple['Client']['Do']);
							if(isset($maple["template"])) self::$_TEMPLATES[$json["namespace"]] = self::$_plugin_list[$maple['ID']]."/".$maple["template"]."/";
 							if(is_array($maple['Client']['Do'])){
 								foreach ($maple['Client']['Do'] as $does) {
 									array_push(self::$_DO,$does);
 								}
 							}
 							else array_push(self::$_DO,$maple['Client']['Do']);
 							foreach($maple['Client']['Plugin'] as $plugin){
 								if(isset(self::$_PLUGINS[$plugin['Bind']])){
 									$temp = $plugin['Bind'];
 									Log::warning("$temp Plugin not loaded from $k : Initially with ".$_PLUGINS['']);
 								}
 								else self::$_PLUGINS[$plugin['Bind']]=$plugin;
 							}
 							foreach ($maple['Shortcode'] as $code => $func) self::$shortcodes[$code] = $func;
 						}
 						else
 						Log::debug('Invalid Client Plugin',json_decode(preg_replace("/\s+/",'',file_get_contents(ROOT.PLG."$k/package.json")),true));
 					}
 				}
 				self::$_SEARCHERS = array_filter(self::$_SEARCHERS);
 				self::$_DO = array_filter(self::$_DO);
 				foreach (self::$_ROUTES as $route) ROUTE::UseFile($route);
 				CACHE::put("maple","active-plugin",[
 						"_FILTERS" 		=> self::$_FILTERS		,
 						"_AUTOLOAD" 	=> self::$_AUTOLOAD		,
 						"_OPTION" 		=> self::$_OPTION		,
 						"_SEARCHERS" 	=> self::$_SEARCHERS	,
 						"_PLUGINS" 		=> self::$_PLUGINS		,
 						"_DO" 			=> self::$_DO			,
 						"shortcodes" 	=> self::$shortcodes	,
 						"_plugin_list" 	=> self::$_plugin_list	,
 						"_ROUTES" 		=> self::$_ROUTES 		,
 						"_TEMPLATES" 	=> self::$_TEMPLATES 	,
 					],[
 						"user-specific" => true,
 					]
 				);
 				Log::info("{$i} plugins were tested but only {$j} loaded");
 			}
			TEMPLATE::add_sources(self::$_TEMPLATES);
 			self::$__plugin_ready = true;
 		}
 	}

	/**
	 * This is an event trigger function that is called in explicitly by the programmer
	 * once called the functions bound to the event will return all data
	 * NOTE : always echo 'MAPLE::do_filters('name',[])' as it implicitly does not
	 * TODO : provide ordered calling functionality and stack handling for cases of recursion
	 * @param  boolean $filter 	name of event
	 * @param  array $param  	any specific parameter
	 * @return text          	a collective dump of function outputs appended
	 */
	public static function do_filters($filter=false,$param=[]){
		$str = '';
		$param["filter"] = $filter;
		ob_start();
		if(isset(self::$_FILTERS[$filter])){
			foreach (self::$_FILTERS[$filter] as $func){
				echo call_user_func($func,$param);
			}
		}
		$str = ob_get_contents();
		ob_end_clean();
		return $str;
	}

	/**
	 * This is explicit event binder that can be called
	 * NOTE : for the purpose of simplicity
	 * 		  prefer adding the filter in your package file
	 * 		  rather than calling this functions.
	 * 		  It is only for dynamic initialization
	 * 		  and has high chance that its 'do_filters' was already called before binding,
	 * 		  hence there may be varied results.
	 * TODO : add option to set priority , send call parameter : filter_name
	 * @param string 	$filter name of event
	 * @param function 	$func   function that needs to trigger with it
	 */
	public static function add_filter($filter,$func){
		if(!isset(self::$_FILTERS[$filter]))
			self::$_FILTERS[$filter] = [];
		array_push(self::$_FILTERS[$filter],$func);
	}

	public static function get_post_url($identifier,$value){
		// TODO : Get Post Url DO this in BLOG plugin
		if(in_array(strtolower($identifier),array('name','title','slug'))) $identifier = ucfirst($identifier);
		$sql = DB::_()->select("posts","*",[
			"$identifier"	=>	$value
		]);
		$count = DB::_()->count("posts",[
			"$identifier"	=>	$value
		]);
		if($count!=0){
			$sql = $sql[0];
			return URL::http("%ROOT%{$sql['Slug']}/");
		}
		else return URL::http("%ROOT%");
	}


	// TODO : Search Hook
	public static function SEARCH(){
		if($_REQUEST['search']){
			if(self::is_admin()) echo self::do_filters('admin_search');
			foreach ($_SEARCHERS as $value) {
				if(function_exists($value))
					echo call_user_func($value);
				else Log::debug('FUNCTION 404',$value);
			}
		}
		else echo "<h2 class='card-title'>Nothing Searched</h2>";
	}

	/**
	 * This function is a shortcode handler that converts all the shortcode into a specified task
	 * The format for a maple shortcode is as follows
	 * [maple plugin plugin2=param2]
	 * where 'maple' specifies and removes any ambiguity in shortcode handling by other CMS or Framework
	 * 		 'plugin','plugin2' is the shortcode name provided by the plugin
	 * 		 'param2' 	is the parametes that need to provided to the shortcode function
	 *
	 *NOTE : to resolve any string display issue that may occure due to
	 *		 the parsing attempt of contents that actually need to be just plain html
	 *		 please use the following syntax : &lsb; maple * &rsb;	FIXME : use html encode for '[' and ']'
	 *		 which is resolved to : [maple * ]
	 *		 and is not executed
	 *
	 * @param text  $content   		the content that needs to be processed
	 * @param boolean $recursive 	to recursively resolve shortcodes
	 * @return text $parsedq		the resolved shortcode
	 */
	public static function Parse($content,$recursive=true){
		$parsed = '';
		$codes = [];
		$parsed_code = [];
		$parsed_s_code = [];
		$codes = PARSER::get_shortcodes($content);
		foreach ($codes as $code) {
			if($code['name']=='maple'){
				$temp = '';
				foreach ($code['attrs'] as $function => $param) {
					ob_start();
					if(isset(self::$shortcodes[$function])){
						call_user_func(self::$shortcodes[$function] ,$param);
					}
					$temp .= ob_get_contents();
					ob_end_clean();
				}
				array_push($parsed_s_code, $code['shortcode']);
				array_push($parsed_code, $temp);
			}
		}
		$parsed = str_replace($parsed_s_code,$parsed_code,$content);
		return $parsed;
	}

	/**
	 * Core Shortcode handler
	 * @param string $param    Shortcode Name
	 * @return html 			Evaluated output
	 */
	public static function shortcode_core($param){
		switch ($param) {
			case 'theme':
					FILE::safe_require(URL::dir("%THEME%index.php"));
				break;
			case 'plugin':
					FILE::safe_require(URL::dir("%PLG%index.php"));
				break;
			case 'include':
					FILE::safe_require(URL::dir("%ROOT%%INC%index.php"));
				break;
			case 'admin':
					FILE::safe_require(URL::dir("%ADMIN%index.php"));
				break;
		}
	}

	public static function plugin_permission_update(){
		if(URL::has_request(["remove-access","permission"])){
			if($_REQUEST["remove-access"] == "all"){
				$levels = json_decode(FILE::read(__DIR__."/config/user-type.json"),true);
				foreach ($levels as $key => $level){
					$file = new _FILE(__DIR__."/config/permission-{$level}.json");
					if($file->exists()){
						$permissions = json_decode($file->read(),true);
						$permissions = array_diff($permissions,$_REQUEST["permission"]);
						$file->write(json_encode($permissions,JSON_PRETTY_PRINT));
					}
				}
			}
		}
		else if(URL::has_request(["remove-access"])){
			echo URL::http("%CURRENT%",[
				"maple_ajax" => "maple",
				"maple_ajax_action"	=>	"remove-access",
				"remove-access"	=>	"all",
				"permissions"	=>	[
					"maple-dahboard","infinity",
				]
			]);
		}
		else if (URL::has_request(["permission"])){
			foreach ($_REQUEST['permission'] as $level => $plugins) {
				$file = new _FILE(__DIR__."/config/permission-$level.json");
				if($file->exists()){
					$buffer = json_decode($file->read(),true);
					foreach ($plugins as $id => $permissions) {
						foreach ($permissions as $name => $junk) {
							array_push($buffer,$name);
						}
					}
					$buffer = array_unique($buffer);
					$file->write(json_encode($buffer));
				} else {
					self::DashMessage([
						"type"	=>	"debug",
						"title"	=>	"Permission-Failed",
						"message"=>	"<pre>".json_encode([
							"file"	=>	$file,
							"level"	=>	$level,
							"permission" => $_REQUEST["permission"][$level]
						],JSON_PRETTY_PRINT)."</pre>",
					]);
				}
			}
		}
		URL::redirect(URL::request("redirect_to"));
	}

	public static function DashMessage($param){
		if(is_array($param)){
			$temp = [];
			$param = array_merge(['title'=>'','message'=>'','occure'=>'once','type'=>'default'],$param);
			if(!isset($_SESSION['maple-dash-message'])){
				$_SESSION['maple-dash-message'] = [];
			}
			array_push($_SESSION['maple-dash-message'],$param);
		}
	}

	public static function show_all_themes($value=''){
		/**
		 * TODO A LOT
		 */
		echo TEMPLATE::Render('maple','theme/home',array(
			'card'	=>	array(
				'image'	=> URL::http("%ROOT%%DATA%image/1.jpg"),
				'image2'	=> URL::http("%ROOT%%DATA%image/2.jpg"),
			),
		));
	}

	public static function sidebar_min($value=''){
		if(class_exists('ADMIN')) return ADMIN::sidebar_min();
		else return false;
	}
};

?>
