<?php
$file = str_replace(URL::http("%THEME%") ,'',URL::http("%CURRENT%") );
$file = (__DIR__.'/'.$file);
if(!file_exists($file)||is_dir($file)){
	Log::debug("File Request",array('file'=>$file,'src'=>$_SERVER['REQUEST_URI']));
	http_response_code(404);
	header("MAPLE: 404 Not Found");
}
else{
	$etag = '"' .  md5($file) . '"';
	$etag_header = 'Etag: ' . $etag;
	header($etag_header);
	if (isset($_SERVER['HTTP_IF_NONE_MATCH']) and $_SERVER['HTTP_IF_NONE_MATCH']==$etag) {
		http_response_code(304);
		header("Cache-Control : public,max-age=864000");
		exit();
	}
	$file = new _FILE($file);
	$file->set_http_header();
	echo $file->read();
}
die();
?>
