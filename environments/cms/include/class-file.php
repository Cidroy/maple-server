<?php
namespace maple\cms;
/**
 * Static method based file handling class
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class FILE{
	/**
	 * List of all mime types based on extention
	 * @var array
	 */
	private static $_mime_list = null;

	/**
	 * get the mime type for file
	 * returns null if file does not exists
	 * returns default mime tye if file does not has any extention or is not listed
	 * @api
	 * @uses \maple\environment\FILE if mime-types file is missing
	 * @param  file-path $file path to FILE
	 * @return mixed[null,string]       mime type
	 */
	public static function mime($file){
		if(self::$_mime_list === null){
			if(!file_exists(\ROOT.\CONFIG."/mime-types.json")){
				LOG::error("Mime types file is missing please add file");
				self::$_mime_list = [];
			} else  self::$_mime_list = json_decode(file_get_contents(\ROOT.\CONFIG."/mime-type.json"),true);
		}
		if(self::$_mime_list){
			$ext = pathinfo($file);
			$ext = isset($ext["extension"])?$ext["extension"]:"none";
			return isset(self::$_mime_list[$ext]) ? self::$_mime_list[$ext] : null;
		} else return \maple\environments\FILE::mime_type($file);
	}

	/**
	 * Get list of all folders in the directory or directory of the file path
	 * returns null if $path is not a valid directory or file path
	 * @api
	 * @param  folder-path $path path to the directory
	 * @return array       list of folders
	 */
	public static function get_folders($path){
		if(is_file($path)) $path = dirname($path);
		$path = rtrim($path,"/");
		if(!file_exists($path)) return null;
		return array_filter(glob("{$path}/*"), 'is_dir');
	}

	/**
	 * Get list of all files in the directory or directory of the file path
	 * returns null if $path is not a valid directory or file path
	 * @api
	 * @param  folder-path $path path to the directory
	 * @return array       list of files
	 */
	public static function get_files($dir){
		if(is_file($path)) $path = dirname($path);
		$path = rtrim($path,"/");
		if(!file_exists($path)) return null;
		return array_filter(glob("{$path}/*"), 'is_file');
	}

	/**
	 * Read all content in file
	 * @api
	 * @throws \maple\cms\exceptions\FileNotFoundException if $report set to false and file does not exists
	 * @throws \maple\cms\exceptions\FilePermissionException if $report set to false and Maple CMS does not has permission to read file
	 * @param  file-path  $path   path to FILE
	 * @param boolean $convert 	if set to true converts the json back to its original form else returns json string
	 * @param  boolean $report if it should report error or throw error
	 * defaults to error reporting.
	 * set false to throw error
	 * @return string	file content
	 */
	public static function read($path,$convert = false,$report = true){
		if(file_exists($path) && is_file($path) && is_readable($path)){
			$content = file_get_contents($path);
			if($convert){
				$temp = null;
				try { $temp = json_decode($content,true); } catch (\Exception $e) { }
				if(json_last_error() === 0 ) return $temp;
			}
			return $content;
		}
		else if($report) Log::debug("unable to read file '{$path}'");
		else{
			if(!file_exists($path)) throw new \maple\cms\exceptions\FileNotFoundException("Unable to read file '{$path}' because file does not exists", 1);
			if(!is_readable($path)) throw new \maple\cms\exceptions\FilePermissionException("Unable to read file '{$path}' because of insufficient permissions", 1);
		}
		return null;
	}

	/**
	 * Write content to file
	 * if content is not of type string then it is converted to json and stored.
	 * @api
	 * @throws \maple\cms\exceptions\FilePermissionException if $report set to false and Maple CMS does not has permission to read write
	 * @param  file-path $path    path to destination
	 * @param  mixed[] $content content to be written
	 * @param  boolean $report if it should report error or throw error
	 * defaults to error reporting.
	 * set false to throw error
	 * @return boolean          status
	 */
	public static function write($path,$content,$report = true){
		if(is_writeable($path)){
			if($report) LOG::error("Unable to write contents to file '{$path}' because of insufficient permissions");
			else throw new \maple\cms\exceptions\FilePermissionException("Unable to write contents to file '{$path}' because of insufficient permissions", 1);
			return false;
		}
		if(!is_string($content)) $content = json_encode($content);
		file_put_contents($path,$content);
		return true;
	}

	/**
	 * Append content to file
	 * if content is not of type string then it is converted to json and stored.
	 * @api
	 * @throws \maple\cms\exceptions\FilePermissionException if $report set to false and Maple CMS does not has permission to read write
	 * @param  file-path $path    path to destination
	 * @param  mixed[] $content content to be written
	 * @param  boolean $report if it should report error or throw error
	 * defaults to error reporting.
	 * set false to throw error
	 * @return boolean          status
	 */
	public static function append($path,$content,$report = true){
		if(is_writeable($path)){
			if($report) LOG::error("Unable to append contents to file '{$path}' because of insufficient permissions");
			else throw new \maple\cms\exceptions\FilePermissionException("Unable to append contents to file '{$path}' because of insufficient permissions", 1);
			return false;
		}
		if(!is_string($content)) $content = json_encode($content);
		file_put_contents($path,$content,FILE_APPEND);
		return true;
	}

	/**
	 * Parse the PHP file and get the string contents
	 * @api
	 * @uses __CLASS__::safe_require
	 * @throws \maple\cms\exceptions\FileNotFoundException if file does not exists
	 * @param  file-path $path Path to file
	 * @param  boolean $convert convert if data is of type json
	 * @return string       content
	 */
	public static function read_render($path,$convert = true){
		$content = null;
		if(file_exists($path)){
			ob_start();
				try { self::safe_require($path); } catch (\Exception $e) { }
				$content = ob_get_contents();
			ob_end_clean();
			if($convert){
				$temp === null;
				try { $temp = json_decode($content); } catch (\Exception $e) { }
				if(json_last_error() === 0 ) return $temp;
			}
			return $content;
		}
		else throw new \maple\cms\exceptions\FileNotFoundException("Unable to read file '{$path}' because file does not exists", 1);
	}

	/**
	 * Returns file size in bytes or human readable format
	 * @param  file-path  $path          path to file
	 * @param  boolean $humanreadable if true returns human readable file size
	 * defaults to true
	 * @return mixed[string,integer]                 file size
	 */
	public static function size($path,$humanreadable=true){
		$size = filesize($path);
		if(!$humanreadable) return $size;
		$sz = 'BKMGTPYZ';
		$factor = floor((strlen($size) - 1) / 3);
		$size = sprintf("%.2f", $size / pow(1024, $factor)) . @$sz[$factor];
		return $size."B";
	}

	/**
	 * delete folder
	 * @api
	 * @throws \maple\cms\exceptions\FilePermissionException if $dir is not deleteable or contains non deleteable files
	 * @param  file-path $dir folder path
	 * @return boolean      status
	 */
	public static function delete_folder($dir) {
		// BUG: does not check deleting permission
		// BUG: does not return proper status
		if (is_dir($dir)) {
			$files = scandir($dir);
			foreach ($files as $file)
				if ($file != "." && $file != "..") rrmdir("$dir/$file");
			rmdir($dir);
		}
		else if (file_exists($dir)) unlink($dir);
		return true;
	}

	/**
	 * Copy contents of folder from source to destination
	 * NOTE : removes any existsing occurence of folder if $merge is false
	 * @api
	 * @throws \maple\cms\exceptions\FilePermissionException if source or destination is not readable or writeable respectively
	 * @throws \maple\cms\exceptions\FileNotFoundException if source does not exists
	 * @param  file-path $src source
	 * @param  file-path $dst destination
	 * @param  boolean $merge if true merges the existsing content with new content
	 * defaults to false
	 * @return boolean      status
	 */
	public static function copy_folder($src, $dst, $merge = false) {
		if ($merge === false && file_exists ( $dst ))
			rrmdir ( $dst );
		if (file_exists ( $src ) && is_dir ( $src )) {
			if(!is_readable($src)) throw new \maple\cms\exceptions\FilePermissionException("Unable to write contents to destination '{$dst}' because of insufficient permissions", 1);
			if(!is_writeable($dst)) throw new \maple\cms\exceptions\FilePermissionException("Unable to read contents from source '{$src}' because of insufficient permissions", 1);
			mkdir ( $dst );
			$files = scandir ( $src );
			foreach ( $files as $file )
				if ($file != "." && $file != "..")
					rcopy ( "$src/$file", "$dst/$file" );
			return true;
		} else throw new \maple\cms\exceptions\FileNotFoundException("Unable to do copy because source '{$src}' does not exists", 1);
		return false;
	}

	/**
	 * Takes in the absolute path to the file and returns an image thumbnail for it
	 * @api
	 * @throws \InvalidArgumentException if Argument #1 & #2 are not of type string and integer respectively
	 * @throws \maple\cms\exceptions\FileNotFoundException if $path does not exists;
	 * @throws \DomainException if $size is less than 32
	 * @param file-path $path full path to file
	 * @param integer $size the size of square thumbnail
	 * @return file-path temporary location of a thumbnail
	 */
	public static function GetThumbnail($path,$size = 32){
		if(!is_string($path)) throw new \InvalidArgumentException("Argument #1 must be a string", 1);
		if(!is_integer($size)) throw new \InvalidArgumentException("Argument #2 must be a string", 1);
		if(!file_exists($path)) throw new \maple\cms\exceptions\FileNotFoundException("'{$path}' is not a valid path", 1);
		if($size < 32) throw new \DomainException("Argument #2 must be greater than 32", 1);

		$path = "";
		// TODO : !important! everything
		return $path;
	}

	/**
	 * Safely 'require_once' file to avoid abrupt termination
	 * @api
	 * @throws \maple\cms\exceptions\FileNotFoundException if $file does not exists
	 * @param  file-path  $file         file path
	 * @param  boolean $critical     is the file critical for execution
	 * defaults to false
	 * @return boolean                status
	 * BUG : does not load it into the caller page but instead loads in the function
	 */
	public static function safe_require_once($file,$critical=false){
		if(file_exists($file)){
			require_once $file;
			return true;
		}
		if($critical) throw new \maple\cms\exceptions\FileNotFoundException("File {$file} does not exists", 1);
		else LOG::warning("File {$file} does not exist");
		return false;

	}

	/**
	 * Safely 'require' file to avoid abrupt termination
	 * @api
	 * @throws \maple\cms\exceptions\FileNotFoundException if $file does not exists
	 * @param  file-path  $file         file path
	 * @param  boolean $critical     is the file critical for execution
	 * defaults to false
	 * @return boolean                status
	 * BUG : does not load it into the caller page but instead loads in the function
	 */
	public static function safe_require($file,$critical=false){
		if(file_exists($file)){
			require $file;
			return true;
		}
		if($critical) throw new \maple\cms\exceptions\FileNotFoundException("File {$file} does not exists", 1);
		else LOG::warning("File {$file} does not exist");
		return false;

	}

	/**
	 * Safely include_once' file to avoid abrupt termination
	 * @api
	 * @throws \maple\cms\exceptions\FileNotFoundException if $file does not exists
	 * @param  file-path  $file         file path
	 * @param  boolean $critical     is the file critical for execution
	 * defaults to false
	 * @return boolean                status
	 * BUG : does not load it into the caller page but instead loads in the function
	 */
	public static function safe_include_once($file,$critical=false){
		if(file_exists($file)){
			return include_once $file;
		}
		if($critical) throw new \maple\cms\exceptions\FileNotFoundException("File {$file} does not exists", 1);
		else LOG::warning("File {$file} does not exist");
		return false;

	}

	/**
	 * Safely 'include' file to avoid abrupt termination
	 * @api
	 * @throws \maple\cms\exceptions\FileNotFoundException if $file does not exists
	 * @param  file-path  $file         file path
	 * @param  boolean $critical     is the file critical for execution
	 * defaults to false
	 * @return boolean                status
	 * BUG : does not load it into the caller page but instead loads in the function
	 */
	public static function safe_include($file,$critical=false){
		if(file_exists($file)){
			return include $file;
		}
		if($critical) throw new \maple\cms\exceptions\FileNotFoundException("File {$file} does not exists", 1);
		else LOG::warning("File {$file} does not exist");
		return false;

	}


	/**
	 * @deprecated maple
	 */
	public static function mime_by_extention($ext){}
	/**
	 * @deprecated maple
	 * @param [type]  $path     [description]
	 * @param boolean $multi    [description]
	 * @param string  $seprator [description]
	 * @param boolean $format   [description]
	 */
	public static function Data($path,$multi=false,$seprator='',$format=false){}
	/**
	 * @deprecated maple
	 */
	public static function parse_read($path){ }
	/**
	 * @deprecated maple
	 * @param [type] $file  [description]
	 * @param array  $param [description]
	 */
	public static function DataHeader($file,$param = array()) { }
	/**
	 * @deprecated maple
	 * @param boolean $param [description]
	 */
	public static function UploadHelper($param = false){}
	/**
	 * @deprecated
	 * @param  [type] $data    [description]
	 * @param  array  $headers [description]
	 * @return [type]          [description]
	 */
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

	/**
	 * Remove File or folders
	 * @api
	 * @throws \InvalidArgumentException if $path is not of type 'string'
	 * @param  string $path path
	 * @return boolean status
	 */
	public static function remove($path){
		if(!is_string($path))	throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!file_exists($path)) return false;
		if(is_file($path)){
			unlink($path);
			return true;
		}
		if(is_dir($path)){
			foreach (glob(rtrim($path,"/")."/*") as $sub_path) self::remove($sub_path);
			rmdir($path);
			return true;
		}
		return false;
	}
}
?>
