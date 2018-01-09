<?php
	/**
	 * Set true in development stage to get any errors in the system
	 * when the product is ready set to false to disable any error or console log to user
	 * @var bool
	 * @package Maple Environment
	 */
	define('PRODUCTION',true);
	/**
	 * Set true to instruct dependent environments to start or stop buffering
	 * @var bool
	 * @package Maple Environment
	 */
	define('BUFFER',false);
	/**
	* For developers: Maple debugging mode.
	* Change this to true to enable the display of notices during development.
	* It is strongly recommended that plugin and theme developers use DEBUG
	* in their development environments.
	* @var bool
	* @package Maple Environment
	*/
	define('DEBUG', true);
	/**
	 * the base directory of everything here
	 * @var string file location of root working directory of maple environment
	 * @package Maple Environment
	 */
	define('ROOT', str_replace("\\","/",__DIR__));

	/**
	 * Allow CORS connection to this site
	 * BUG : allows all CORS
	 * TODO : !important! allow specific connections only which have a secret key
	 */
	if(isset($_SERVER['HTTP_ORIGIN'])) header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
	/**
	 * Basic setup for a production and debugging server configuration
	 */
	if(PRODUCTION){
		ini_set('display_errors',1);
		ini_set('display_startup_errors',1);
		error_reporting(E_ALL);
		ini_set('xdebug.var_display_max_depth', 5);
		ini_set('xdebug.var_display_max_children', 256);
		ini_set('xdebug.var_display_max_data', 1024);
	}
	else{
		ini_set('display_errors','Off');
		error_reporting(0);
	}

	/**
	 * Conver bytes to human readable file size
	 * @param  integer $bytes    file size in bytes
	 * @param  integer $decimals precesion
	 * @return string            file size X.XX [A-Z]B
	 * @package Maple Environment
	 */
	function human_filesize($bytes = 0, $decimals = 2) {
	    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
	    $factor = floor((strlen($bytes) - 1) / 3);
	    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	}

	/**
	 * url details for working of environment
	 * @var must contain "ENCODING","DOMAIN","BASE","DYNAMIC"
	 */
	$URL = [
		"ENCODING"	=>	"https://",
		"DOMAIN"	=>	"localhost",
		"BASE"		=>	"/mframework",
		"DYNAMIC"	=>	true,
	];
	require( __DIR__.'/environments/index.php' );
?>
