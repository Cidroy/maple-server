<?php 
/**
* Breadcrumbs class to add breadcrumbs
* @package Maple Framework
*/
class BREADCRUMBS{
	private static $crumbs;

	public static function Initialize(){ BREADCRUMBS::$crumbs=array(); }

	public static function Get(){return BREADCRUMBS::$crumbs;}
	public static function Display(){ FILE::safe_require_once(ROOT.THEME_USE.'elements/breadcrumbs.php'); }
	public static function Add($name,$url){ BREADCRUMBS::$crumbs[$name]=$url; }

}
?>