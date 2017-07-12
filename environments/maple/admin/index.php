<?php
	MAPLE::is_admin_page(true);
	/**
	* this is admin class all the bindings related to the admin system should be attched to this class
	* @package Maple Framework
	*/
	class ADMIN extends MAPLE{
		private static $_PLUGINS 	=	[];
		private static $_SEARCHERS	=	[];
		private static $_DASH_CARD	=	[];
		private static $_post_content = [];

		public static function Initialize(){
			$cached  = CACHE::get("maple","active-admin-plugin",[],["user-specific"=>true]);
			if($cached){
				self::$_PLUGINS		= $cached["_PLUGINS"];
				self::$_SEARCHERS	= $cached["_SEARCHERS"];
				self::$_DASH_CARD	= $cached["_DASH_CARD"];
			} else {
				foreach (FILE::get_folders(ROOT.PLG) as $k){
					$json=ROOT.PLG."$k/package.json";
					if(file_exists($json)){
						$json = json_decode(file_get_contents($json),true);
						if(isset($json['Maple'])){
							$maple = array_merge([
								"Active"	=>	false,
								"Autoload"	=>	[],
								"Admin"		=>	[]
							],$json['Maple']);
							if(!$maple['Active']) continue;
							$maple['Admin'] = array_merge([
								"Dashboard"	=>	[],
								"Search"	=>	false,
								"Plugin"	=>	[]
							],$maple['Admin']);
							foreach($maple['Admin']['Dashboard'] as $card){
								if(!is_array($card))
									array_push(self::$_DASH_CARD, $card);
								else{
									if(isset($card['Permission'])){
										if(SECURITY::has_access($card['Permission']))
											array_push(self::$_DASH_CARD,$card['Function']);
									}
								}
							}
							array_push(self::$_SEARCHERS,$maple['Admin']['Search']);
							foreach($maple['Admin']['Plugin'] as $plugin){
								if(isset($plugin['Permission'])){
									if(!SECURITY::has_access($plugin['Permission']))	continue;
								}
								if(isset(self::$_PLUGINS[$plugin['Bind']])){
									$temp = $plugin['Bind'];
									Log::warning("$temp Plugin not loaded from $k : Initially with ".json_encode(self::$_PLUGINS[$plugin['Bind']]));
								}
								else{
									$___temp = array();
									if(isset($plugin['More'])){
										foreach ($plugin['More'] as $sub) {
											if(isset($sub['Permission'])){
												if(SECURITY::has_access($sub['Permission']))
													array_push($___temp,$sub);
											}
											else array_push($___temp,$sub);
										}
									}
									$plugin['More'] = $___temp;
									self::$_PLUGINS[$plugin['Bind']]=$plugin;
								}
							}
						}
						else
							Log::debug('Invalid Admin Plugin',preg_replace("/\s+/",'',file_get_contents(ROOT.PLG."$k\\package.json")));
					}
				}
				self::$_SEARCHERS = array_filter(self::$_SEARCHERS);
				CACHE::put("maple","active-admin-plugin",[
					"_PLUGINS"		=>	self::$_PLUGINS ,
					"_SEARCHERS"	=>	self::$_SEARCHERS,
					"_DASH_CARD"	=>	self::$_DASH_CARD,
				],["user-specific"=>true]);
			}
		}

		public static function register_plugin($plugin,$content,$special=false,$category=''){
			if($special){
				switch ($category) {
					case 'settings': break;
					default: Log::error("$plugin[0] not registered, '$category' is not a type."); break;
				}
			}
			else{
				if(!isset($_PLUGINS[$plugin[0]])){
					self::$_PLUGINS[$plugin[0]]  = $plugin[1];
					self::$_PLUGINS_C[$plugin[0]]= $content;
				}
			}
		}

		public static function getPluginNames(){	return self::$_PLUGINS; }
		public static function getDashCards(){	return self::$_DASH_CARD; }

		public static function AdminSidebar(){ return self::$_PLUGINS; }
		public static function SearchHandler(){
			//TODO :
		}

		public static function sidebar_min($value=''){
			return TEMPLATE::Render('maple','admin/sidebar.min',array(
				'sidebar' => array(
					'title' => '<i class="material-icons left large">stars</i>Maple',
					'content' => self::AdminSidebar(),
				)
			));
		}

		public static function Content(){
			$str = "
				<div class='row'>
					<div class='col s12 l2 hide-on-med-and-down'>
						{{ sidebar|raw }}
					</div>
					<div class=\"col s12 l10\">
						{{ messages|raw }}
						{{ content|raw }}
					</div>
				</div>
			";
			$temp = array();
			if(!isset($_SESSION['maple-dash-message']))	$_SESSION['maple-dash-message'] = array();
			foreach ($_SESSION['maple-dash-message'] as $key => $value) {
				if($value['occure']=='once') array_push($temp,$key);
			}
			$cc = self::_load_function(2);
			$cc = $cc ? $cc : self::_resolve_post_content() ;
			if(!$cc && \URL::http("%CURRENT%") == \URL::http("%ADMIN%") ){
				UI::title()->add('Dashboard');
				$content = FILE::parse_read(__DIR__."/content.php");
			}
			else if (!$cc){
				$content = TEMPLATE::Render("maple","error/404",[]);
			}
			else $content = $cc;
			echo TEMPLATE::RenderText($str,[
				"sidebar"	=>	TEMPLATE::Render('maple','admin-sidebar',[
					'sidebar' => [
						'title' => '<i class="material-icons left large">stars</i>Maple',
						'content' => self::AdminSidebar(),
					]
				]),
				"messages"	=>	TEMPLATE::Render('maple','admin-message',[
					'messages'	=>	$_SESSION['maple-dash-message'],
				]),
				"content"	=>	$content,
			]);
			MAPLE::add_filter('admin_sidebar_list','self::sidebar_min');
			foreach ($temp as $key => $value) {
				$_SESSION['maple-dash-message'][$value] = null; 
				unset($_SESSION['maple-dash-message'][$value]);
			}
		}

		private static function _load_function($i){
			$url = URL::page($i);
			if(isset(self::$_PLUGINS[$url])){
				$i=$i+1;
				ob_start();
				if(isset(self::$_PLUGINS[$url]['More'])&&URL::page($i)){
					foreach(self::$_PLUGINS[$url]['More'] as $c){
						if(isset($c['Permission'])){	if(!SECURITY::has_access($c['Permission']))	continue;	}
						if($c['Bind']==URL::page($i)){
							UI::title()->add($c["Name"]);
							call_user_func($c['To']);
						}
					}
					UI::title()->add(self::$_PLUGINS[$url]["Name"]);
				}
				else{
					call_user_func(self::$_PLUGINS[$url]['To']);
				}
				$x = ob_get_contents();
				ob_end_clean();
				return $x;
			}
			return false;
		}

		public static function add_content($data,$count = false,$priority = 10){
			self::$_post_content[$priority][] = array_merge([
				"count"	=>	$count,
				"priority"	=>	$priority,
			],$data);
		}

		private static function _resolve_post_content(){
			$content = "";
			ob_start();
			foreach (self::$_post_content as $priority => $contents) {
				foreach ($contents as $content) {
					if(isset($content["function"])) call_user_func($content["function"]);
					if(isset($content["content"])) echo $content["content"];
				}
			}
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

	}

	if(SECURITY::has_access("maple-dashboard")){
		ADMIN::Initialize();
		UI::css()->add_src(URL::http("%THEME%Admin/style.css"));
		UI::js()->add_src(URL::http("%THEME%Admin/admin.js"));
		UI::content()->add('ADMIN::Content','');
		TEMPLATE::$full_content=true;
	}
?>
