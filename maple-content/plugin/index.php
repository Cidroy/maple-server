<?php
$file = str_replace(URL::http("%PLUGIN%") ,'',URL::http("%CURRENT%") );
$file = (__DIR__.'/'.$file);
if(!is_dir($file)){
	$ext  = pathinfo($file)['extension'];
	$deny = array( 'json' );
	if(in_array( $ext, $deny)){
		http_response_code(404);
		die();
	}
}
if( !file_exists($file) || is_dir($file) ){
	Log::debug("Plugin File not Found",array(
		'url' => URL::http("%CURRENT%"),
		'file'=> $file
		));
	http_response_code(404);
}
else{
	$file = new _FILE($file);
	$file->set_http_header();
	echo $file->read();
}
die();
?>
