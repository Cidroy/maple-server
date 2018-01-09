<?php

/**
 * this is an object class for file data
 */
class _FILE{
	private $details = [
		"name"				=>	"",
		"basename"			=>	"",
		"location"			=>	"",
		"directory"			=>	"",
		"extension"			=>	"",
		"mime"				=>	"",
		"size"				=>	0,
		"exists"			=>	false,
		"permission"		=> 0,
		"owner"				=>	"",
		"last-access-time"	=>	null,
		"modify-time"		=>	null,
		"cache-header"		=>	"",
	];

	function __construct($file){
		$this->details["exists"] = file_exists($file);
		$data   = pathinfo($file);
		$this->details["name"] 		= $data["filename"];
		$this->details["basename"] 	= $data["basename"];
		$this->details["location"] 	= $file;
		$this->details["directory"] = $data["dirname"];
		$this->details["extension"] = isset($data["extension"])?$data["extension"]:"none";
		$this->details["mime"] 		= FILE::mime($file);
		$this->details["size"] 		= FILE::size($file);
		$this->details["cache-header"]= CACHE::get_mime_http_header($this->details["mime"]);

		if($this->details["exists"]){
			$this->details["permission"]= fileowner($file);
			$this->details["owner"] 	= fileperms($file);
			$this->details["last-access-time"]= fileatime($file);
			$this->details["modify-time"]= filemtime($file);
		}
	}

	public function name()			{ return $this->details["name"];  }
	public function basename()		{ return $this->details["basename"];  }
	public function location()		{ return $this->details["location"];  }
	public function directory()		{ return $this->details["directory"];  }
	public function extension()		{ return $this->details["extension"];  }
	public function mime()			{ return $this->details["mime"];  }
	public function size($x = false){
		if($x) return filesize($this->details["location"]);
		return $this->details["size"];
	}
	public function exists()		{ return $this->details["exists"];  }
	public function permission($param = null)	{
		if(is_array($param)){
			foreach ($param as $value) {
				switch ($value) {
					case 'w': return is_writable($this->details["location"]); break;
					case 'r': return is_readable($this->details["location"]); break;
				}
			}
		}
		if($param === null) return $this->details["permission"];
	}
	public function grant($param){
		try{
			return chmod($this->details["location"],octdec($param));
		}catch(Exception $e){
			return false;
		}
	}
	public function owner()			{ return $this->details["owner"];  }
	public function last_access()	{ return $this->details["last-access-time"];  }
	public function last_modified()	{ return $this->details["modify-time"];  }
	public function cache_header()	{
		$etagFile = md5($this->location().$this->last_modified());

		header_remove("Expires");
		header_remove("Etag");
		header_remove("Pragma");
		header_remove("Last-Modified");
		header("Content-Length: {$this->size(true)}");
		header("Last-Modified:".gmdate("D, d M Y H:i:s",$this->last_modified()));
		header("Etag: $etagFile");
		 return $this->details["cache-header"];
	 }
	public function set_http_header(){
		header("Content-Type: {$this->mime()}");
		header("Expires: ".gmdate("D, d M Y H:i:s",time() + 868000)." GMT" );
		header("Cache-Control: {$this->cache_header()}");
	}

	public function copy($destination){
		return copy($this->details["location"],$destination);
	}

	public function delete(){
		unlink($this->details["location"]);
	}

	public function rename($name){
		return rename($this->details["location"],$this->details["directory"]."/$name");
	}

	public function set_permission($permission){
		return chmod($this->details["location"], $permission);
	}

	public function read(){
		return file_get_contents($this->details["location"]);
	}

	public function write($data){
		if( !$this->exists() && !file_exists($this->directory()) ){
			mkdir($this->directory());
		}
		if(is_array($data)) $data = json_encode($data);
		return file_put_contents($this->details["location"], $data);
	}
}


/**
 * this is file handling class
 * @package Maple Framework
 */
class FILE{
	private static $_mime_list = null;

	public static function mime($file){
		if(!self::$_mime_list){
			self::$_mime_list = json_decode(file_get_contents(__DIR__."/config/mime-type.json"),true);
		}
		$ext = pathinfo($file);
		$ext = isset($ext["extension"])?$ext["extension"]:"none";
		return isset(self::$_mime_list[$ext]) ? self::$_mime_list[$ext] : null;
	}

	public static function mime_by_extention($ext){
		if(!self::$_mime_list){
			self::$_mime_list = json_decode(file_get_contents(__DIR__."/config/mime-type.json"),true);
		}
		return isset(self::$_mime_list[$ext]) ? self::$_mime_list[$ext] : null;
	}

	public static function get_folders($path){
		$dirs=[];
		$dir = dir($path);
		while (false!==($entry = $dir->read())) {
			if(is_dir(ROOT.PLG.'/'.$entry)&&!in_array($entry,['.','..']))
				$dirs[]=$entry;
		}
		return $dirs;
	}

	public static function get_files($dir){
		$res = [];
		if (is_dir($dir)){
		  if ($dh = opendir($dir)){
		    while (($file = readdir($dh)) !== false){
				if(!is_dir($file) && !in_array($file,['.','..']))
				array_push($res,$file);
		    }
		    closedir($dh);
		  }
		}
		return $res;
	}

	public static function read($path,$report = true){
		if(file_exists($path)){ return file_get_contents($path); }
		else if($report){
			Log::debug("READ FILE Failed",$path);
			return NULL;
		}
	}

	public static function write($path,$content){file_put_contents($path,$content);}
	public static function append($path,$content){file_put_contents($path,$content,FILE_APPEND);}

	public static function parse_read($path){
		$content = NULL;
		if(file_exists($path)){
			ob_start();
				FILE::safe_require($path);
				$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
		else {
			Log::debug("READ FILE Failed",$path);
			return NULL;
		}
	}

	public static function size($path,$sure=false){
		$good = $sure?true:file_exists($path);
		if($good) $good = filesize($path);
		else return "0B";
		$sz = 'BKMGTPYZ';
		$factor = floor((strlen($good) - 1) / 3);
		$good = sprintf("%.2f", $good / pow(1024, $factor)) . @$sz[$factor];
		return $good."B";
	}

	public static function delete_folder($dir) {
	    if (is_dir($dir)) {
	        $files = scandir($dir);
	        foreach ($files as $file)
	            if ($file != "." && $file != "..") rrmdir("$dir/$file");
	        rmdir($dir);
	    }
	    else if (file_exists($dir)) unlink($dir);
	}

	public static function copy_folder($src, $dst) {
	    if (file_exists ( $dst ))
	        rrmdir ( $dst );
	    if (is_dir ( $src )) {
	        mkdir ( $dst );
	        $files = scandir ( $src );
	        foreach ( $files as $file )
	            if ($file != "." && $file != "..")
	                rcopy ( "$src/$file", "$dst/$file" );
	    } else if (file_exists ( $src ))
	        copy ( $src, $dst );
	}

	public static function Data($path,$multi=false,$seprator='',$format=false){
		$files = array();
		if($multi){
			$path = explode($seprator,$path);
			foreach ($path as $k)
				array_push($files,$k);
		}
		else array_push($files,$path);
		$data = array();
		foreach ($files as $fil) {
			$file = URL::dir($fil);
			array_push($data,array(
					"Name"=> 'name'/* TODO : Where is the name boy?*/,
					"Url" => str_replace('\\','/', URL::http("%ROOT%{$file}")),
					"Size"=> FILE::size($file),
					"Exists"=> file_exists($file),
				));
		}
		if(!$format) return $data;
		else{
			$str='';
			foreach ($data as $file){
				$str .= str_replace(array("{{Url}}","{{Size}}","{{Name}}","{{Missing}}"),array($file['Url'],$file['Size'],$file['Name'],$file["Exists"]),$format);
			}
			return $str;
		}
	}

	public static function DataHeader($file,$param = array()) {
		// TODO : append header
		// include 	cache , mime-type
	}

	public static function UploadHelper($param = false){
		$p ='';
		$str = "
			<input {{param}}>
			<a class='modal-trigger waves-effect waves-light btn red white-text' href='#maple_file_upload'><i class='material-icons left'>insert_photo</i>Choose File</a>";
		if($param) foreach ($param as $key => $value) $p .= " $key='$value'";
		$p .= isset($param['name'])?'':"name='files'";
		$p .= isset($param['type'])?'':"name='text'";
		$str = str_replace(array("{{param}}"),array($p),$str);
		UI::js()->add_src(URL::http("%PLUGIN%File-Upload/file-uploader.js"));
		MAPLE::do_filters('file_upload_form');
		echo $str;
	}

	/**
	 * Takes in the absolute path to the file and returns an image thumbnail for it
	 * @param path : full path to file
	 * @param size : the size of thumbnail
	 * @return image-url : temporary location of a thumbnail
	 */
	public static function GetThumbnail($path,$size){
		// TODO :
		/*
			Test if file or folder
			Test if previously generated a thumbnail
			return prev or new thumbnail
		*/
	}

	public static function download($data,$headers = []){
		if(is_array($data)){
			header("Content-Type: {$data['header']['Content-Type']}");
			header("Content-Disposition: attachment; filename={$data['name']}");
			header('Pragma: no-cache');
			echo $data['content'];
			die();
		} else {
			$file = new _FILE($data);
			$file->set_http_header();
			header("Content-Type: {$this->mime()}");
			header("Content-Description: File Transfer");
			header("Content-Disposition: attachment; filename=\"{$file->basename()}\"");
			header("Pragma: public");
			foreach ($headers as $value) header($value);
			readfile($file->location);
		}
	}

	private static function __exception($file,$id,$critical){
		Log::error("File $id - ".$file);
		if($critical)  ERROR::Exception("$file",$id);
		return false;
	}

	/**
	 * Safe load files to stop abrupt termination
	 * @param file location
	 * @return bool true if loaded
	 * BUG : does not load it into the caller page but instead loads in the function
	 */
	public static function safe_require_once($file,$critical=true,$report_debug=true){
		if(file_exists($file)){
			require_once $file;
			return true;
		}
		else if($report_debug) return self::__exception($file,404,$critical);
	}
	public static function safe_require($file,$critical=true,$report_debug=true){
		if(file_exists($file)){
			require $file;
			return true;
		}
		else if($report_debug) return self::__exception($file,404,$critical);
	}
	public static function safe_include_once($file,$critical=true,$report_debug=true){
		if(file_exists($file)){
			return include_once $file;
		}
		else if($report_debug) return self::__exception($file,404,$critical);
	}
	public static function safe_include($file,$critical=true,$report_debug=true){
		if(file_exists($file)){
			return include $file;
		}
		else if($report_debug) return self::__exception($file,404,$critical);
	}
}
?>
