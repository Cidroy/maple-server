<?php
	/**
	* This is the template class that is mandatory to add any contents to the UI
	* @package Maple Framework
	*/
	/*
	TODO : add request settings
		- maple render
			-	with/without template
			-	only content
		- execute with request param
		- change theme render tech ... use twig in functions
		 	- class THEME_USE implements _TEMPLATE_THEME_
		- render from file,string
		- move maple render functions to class CMS
	 */
	require_once ROOT.INC."/template/class-render-engine.php";
	require_once ROOT.INC."/template/class-ui.php";

	class TEMPLATE extends RenderEngine{
			public static $full_content	= false;
	};
?>
