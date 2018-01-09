<?php
namespace materialize;

class THEME implements \Maple\cms\iTheme{
	const _NAMESPACE = "rinzler/materialize";

	public static function initialize(){
		\TEMPLATE::add_sources([ self::_NAMESPACE => __DIR__ ]);
		\TEMPLATE::add_default_source(__DIR__);
		\UI::css()->add_src(__DIR__."/css/materialize.min.css");
		\UI::css()->add_src(__DIR__."/css/style.css");
		\UI::css()->add_src(__DIR__."/fonts/icons/material-icons.css");
		\UI::js()->add_src("%THEME%jquery.js");
		\UI::js()->add_src(__DIR__."/js/materialize.min.js");
	}

	public static function renderHead($param){
		return \TEMPLATE::Render(self::_NAMESPACE,"header",$param);
	}

	public static function renderFooter($param){
		return \TEMPLATE::Render(self::_NAMESPACE,"footer",$param);
	}

	public static function renderContent($param){
		// return [
		// 	"type"	=>	"error",
		// 	"content"=>	"",
		// 	"error"	=>	404,
		// ];
		return [
			"type"	=>	"success",
			"content"=>	\TEMPLATE::Render(self::_NAMESPACE,"index",$param),
		];
	}

	public static function renderError($errorCode) {
		return	\TEMPLATE::render(self::_NAMESPACE,"error/{$errorCode}",[]);
	}
}
?>
<?php THEME::initialize(); ?>
