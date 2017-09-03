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
		return 'Maple CMS Twig Extension';
	}

	public function getFilters(){
		return [
			"unique"	=>	new \Twig_Filter_Method($this,"unique")
		];
	}

	public function getFunctions() {
		return [
			"json_encode"	=>	new \Twig_Function_Function("json_encode"),
			"json_decode"	=>	new \Twig_Function_Function("json_decode"),
			"call"	=>	new \Twig_Function_Function(__CLASS__."::maple_call"),
			"lang"	=>	new \Twig_Function_Function(__CLASS__."::translator"),
			"url"	=>	new \Twig_Function_Function(__CLASS__."::url"),
			"path"	=>	new \Twig_Function_Function("\\maple\\cms\\URL::dir"),
			"filter"=>	new \Twig_Function_Function("\\maple\\cms\\MAPLE::do_filters"),
			"widget"=>	new \Twig_Function_Function(__CLASS__."::widget"),
			"ajax"	=>	new \Twig_Function_Function(__CLASS__."::ajax_input"),
			"nonce"	=>	new \Twig_Function_Function(__CLASS__."::nonce"),
			"permission"=>	new \Twig_Function_Function("\\maple\\cms\\SECURITY::permission"),
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

	/**
	 * Return Ajax Input Form Field
	 * @uses \maple\cms\AJAX::request_parameters
	 * @return string html
	 */
	public static function ajax_input($namespace,$action){
		return "<input type=\"text\" name=\"".\maple\cms\AJAX::request_parameters["namespace"]."\" value=\"{$namespace}\" hidden=\"true\"><input type=\"text\" name=\"".\maple\cms\AJAX::request_parameters["action"]."\" value=\"{$action}\" hidden=\"true\">";
	}

	/**
	 * Return Nonce Form Field
	 * @uses \maple\cms\SECURITY::generate_nonce
	 * @return string html
	 */
	public static function nonce($life = false){
		return "<input type=\"text\" name=\"".\maple\cms\SECURITY::nonce_request_name."\" value=\"".\maple\cms\SECURITY::generate_nonce($life)."\" hidden=\"true\">";
	}

	/**
	 * Return Url
	 * NOTE : This function can process both named url and normal url
	 *  - if argument #1 and argument #2 are of type 'string' then it calls \maple\cms\URL::name,
	 *    in this case the argument #3 can be used for query building
	 *  - if argument #1 is only passed then it calls \maple\cms\URL::http,
	 *    here, argument #2 can be used for query building
	 * @param  string $a1 pseudo-url/url or namespace
	 * @param  mixed[array,string]  $a2 query parameters or name url
	 * @param  array  $a3 query parameters for name url
	 * @return string     url
	 */
	public static function url($a1,$a2 = [],$a3 = []){
		$url = "";
		if(is_string($a1) && is_array($a2)) $url = \maple\cms\URL::http($a1,$a2);
		if(is_string($a1) && is_string($a2)) $url = \maple\cms\URL::name($a1,$a2,$a3);
		return $url;
	}

	/**
	 * Unique Filter for arrays
	 * @param  mixed $array array
	 * @return mixed        unique array
	 */
	public function unique($array){
		if(is_array($array)) return array_unique($array);
		else return $array;
	}

}

?>
