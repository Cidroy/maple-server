<?php
namespace Maple\Cms;

interface iTheme{
	public static function renderHead($content);
	public static function renderContent($content);
	public static function renderFooter($content);
	public static function renderError($e);
}
?>
