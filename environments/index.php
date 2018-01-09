<?php
define('ENVIRONMENT', __DIR__);
require_once __DIR__."/system/index.php";

try {
	ENVIRONMENT::initialize(true);
	if(ENVIRONMENT::direct()){ echo ENVIRONMENT::content() ; die(); }
	if(ENVIRONMENT::execute()) echo ENVIRONMENT::content();
	else ENVIRONMENT::error();
} catch (Exception $e) {
	if(ENVIRONMENT::locked()) ENVIRONMENT::reset_lock();
	if(DEBUG) throw $e;
}
?>
