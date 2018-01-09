<?php
namespace maple\environments;

/**
 * Interface for the maple environment compatibility
 * TODO : !important! environment test if implements environment
 */
interface iRenderEnvironment{
	public static function initialize();
	public static function load();
	public static function execute();
	public static function direct();
	public static function has_content();
	public static function content();
	public static function error($param);
}
?>
