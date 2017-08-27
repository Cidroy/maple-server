<?php
namespace maple\cms\twig;

/**
 * Twig Extension class for maple
 * @since 1.0
 * @package Maple CMS
 * @subpackage Maple Twig Render Engine
 * @author Rubixcode
 */
class Maple_Twig_Ext extends \Twig_Extension{
	public function getName(){
		return 'maple';
	}

	public function getFunctions() {
		return [
			"call"	=>	new \Twig_Function_Function(__CLASS__."::maple_call"),
			"lang"	=>	new \Twig_Function_Function(__CLASS__."::translator"),
			"url"	=>	new \Twig_Function_Function("\\maple\\cms\\URL::http"),
			"path"	=>	new \Twig_Function_Function("\\maple\\cms\\URL::dir"),
			"filter"=>	new \Twig_Function_Function("\\maple\\cms\\MAPLE::do_filters"),
			"permission"=>	new \Twig_Function_Function("\\maple\\cms\\SECURITY::permission"),
			"widget"	=>	new \Twig_Function_Function(__CLASS__."::widget"),
		];
	}

	/**
	 * Call function
	 * @param  string $function function name
	 * @param  mixed[] $param    parameters list
	 * @return string           output
	 */
	public static function maple_call($function,$param = null){
		if(!is_string($function))	throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		$out = "";
		try { $out = call_user_func($function,$param); } catch (\Exception $e) {}
		return (
			$out?
			(is_string($out)?$out:json_encode($out,JSON_PRETTY_PRINT))
			:""
		);
	}

	/**
	 * Translate to the neccessary language
	 * BUG : does nothing
	 * @param  string $namespace app namespace
	 * NOTE : use maple/cms for default
	 * @param  string $string string
	 * @param  string $lang   language
	 * @return string         translated language
	 */
	 public static function translator($namespace,$string,$lang = null){
 		return $string;
 	}

	/**
	 * Return Widget
	 * BUG : does nothing
	 * TODO : implement widget system
	 * @return string html
	 */
	public static function widget(){
		return "";
	}

}

?>
