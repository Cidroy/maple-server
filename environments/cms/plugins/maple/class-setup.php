<?php
namespace maple\cms\plugin;
use \maple\cms\DB;

/**
 * Setup class
 * @since 1.0
 * @package Maple CMS
 * @subpackage Maple CMS Plugin
 */
class SETUP{

	/**
	 * initialize setup
	 * @uses maple\cms\DB::initialized()
	 */
	public static function initialize(){
		if(!DB::initialized()) return;
		echo "setup init";
	}
}

SETUP::initialize();
?>
