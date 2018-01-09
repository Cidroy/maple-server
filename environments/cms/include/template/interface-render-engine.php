<?php
namespace maple\cms\interfaces;

/**
 * Interface for Communication
 */
interface iRenderEngine{
	public static function initialize();
	public static function render($namespace,$template,$data = []);
	public static function render_text($text,$data = []);
	public static function render_file($file,$data = []);
	public static function add_template_sources($namespaces);
	public static function add_default_template_source($source);
	public static function debug();
}
?>
