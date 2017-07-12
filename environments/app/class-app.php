<?php
namespace maple\app;

class APP{
	private static $_conf_temp = null;

	public static function configuration($applet) {
		if(self::$_conf_temp === null) self::$_conf_temp = \maple\environments\eAPP::configuration();
		if(isset(self::$_conf_temp["apps"][$applet])){
			$conf = self::$_conf_temp["configurations"][$param];
			$conf["configuration"] = json_decode(file_get_contents(self::$_conf_folder.$conf["configuration"].".json"),true);
			return $conf;
		}
		else if( is_dir(ROOT.self::$_conf_temp["base-folder"]."/{$applet}") ){
			$conf = json_decode(file_get_contents(ROOT.self::$_conf_temp["base-folder"]."/{$applet}/package.json"),true);
			$conf = isset($conf["maple"])?(isset($conf["maple"]["maple/app"])?$conf:false):false;
			if($conf){
				$temp = $conf;
				unset($temp["maple"]);
				$conf = $conf["maple"]["maple/app"];
				if(isset(self::$_conf_temp["configurations"][$applet]))
					$conf["url"] = self::$_conf_temp["configurations"][$applet]["url"];
				$conf["details"] = $temp;
				return $conf;
			}
			return false;
		}
		return false;
	}

	public static function apps($param){
		switch ($param) {
			case 'active': return \maple\environments\eAPP::configuration()["apps"]; break;
			case '*':
				$all = [];
				foreach (array_filter(glob(ROOT.self::$_conf_temp["base-folder"]."*"), 'is_dir') as $dir) {
					$package = "{$dir}/package.json";
					if(file_exists($package)){
						$package = file_get_contents($package);
						$package = json_decode($package,true);
						if(isset($package["maple"]) && isset($package["maple"]["maple/app"])){
							$package["maple"]["maple/app"]["location"] = str_replace(ROOT.self::$_conf_temp["base-folder"],"",$dir);
							$all[$package["maple"]["maple/app"]["location"]] = $package["maple"]["maple/app"];
						}
					}
				}
				return array_merge($all,self::apps("active"));
				break;
			default: return false; break;
		}
	}

}

?>
