<?php
$headers = [];
$file = ENVIRONMENT::serve([
	"source"	=>	__DIR__,
	"url"		=>	\maple\environments\eMAPLE::install_url,
	"deny"		=>	[".*\.php",".*\.json"]
],$headers);

if($file){
	ENVIRONMENT::headers($headers);
	readfile($file);
	die();
}
else if(ENVIRONMENT::url()->current()===ENVIRONMENT::url()->root(\maple\environments\eMAPLE::install_url."/")){
	require_once 'class-setup.php';
	\maple\cms\SETUP::initialize();
	die();
}
?>
