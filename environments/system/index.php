<?php
namespace maple\environments;
require_once __DIR__."/class-exceptions.php";
/**
 * Basic url functionality for maple environment
 * @package Maple Environment
 * @since 1.0
 */
class __URL{
	/**
	 * store url details such as encoding,domain,base
	 * @var array
	 */
	private $_content = [];
	/**
	 * buffer for registered url/queries
	 * @var array
	 */
	private $_reg_urls = null;
	/**
	 * path to file containing registered url/query
	 * @var file-path
	 */
	const url_dir = __DIR__."/config/url-registered.json";

	/**
	 * set basic details for further use of this object
	 * @throws BadMethodCallException if $param does not contain any of the array key "ENCODING","DOMAIN","BASE"
	 * @param  array  $param initializes the object
	 */
	public function initialize(array $param) {
		if( isset($param["ENCODING"]) && isset($param["DOMAIN"]) && isset($param["BASE"]) ) {
			$this->_content = $param;
			$this->_content["DOMAIN"] = $_SERVER["SERVER_NAME"];
		}
		else throw new \BadMethodCallException("Insufficent parameters provided, expecting 'ENCODING','DOMAIN','BASE' as array keys ", 1);
	}
	/**
	 * @return string url encoding ( HTTP / HTTPS )
	 */
	public function encoding(){ return $this->_content["ENCODING"]; }
	/**
	 * @return string domain name
	 */
	public function domain(){ return $this->_content["DOMAIN"]; }
	/**
	 * @return string domains sub path
	 */
	public function base(){ return $this->_content["BASE"]; }
	/**
	 * @param  string $extend [optional] url to be attached at the end, must start with /. Defaults to /.
	 * @return string         site url
	 */
	public function root( $extend = "/" ){
		return $this->_content["ENCODING"].$this->_content["DOMAIN"].$this->_content["BASE"].$extend;
	}
	/**
	 * NOTE : Does not return the queries
	 * @return string current url
	 */
	public function current(){
		$current = explode('?',"$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")[0];
		return $this->_content["ENCODING"].$current;
	}
	/**
	 * return availablity of url or query to be set as permanent redirection or priority
	 * NOTE : if the url/query belongs to the caller environment then also it will return true
	 * NOTE : query type cannot contain symbols such as ?,&
	 * @api
	 * @throws InvalidArgumentException if $environment not type 'string'
	 * @throws InvalidArgumentException if $url not type 'string' or 'array'
	 * @uses self::identify_environment to get environment for param $url
	 * @param  string $environment name of environment to test for
	 * @param  string/array $url	if string checks for url else tries for query
	 * @return bool
	 */
	public function available($environment,$url){
		if(!is_string($environment)) throw new \InvalidArgumentException("Argument #1 must be an instance of 'string', but '".gettype($url)."' given", 1);
		if(is_array($url) || is_string($url)) return self::identify_environment($url)?(self::identify_environment($url) == $environment):true;
		else throw new \InvalidArgumentException("Argument #2 must be an instance of string, but '".gettype($url)."' given", 1);
	}
	/**
	 * returns the environment that a perticular url/query belongs to
	 * @api
	 * @throws InvalidArgumentException if $url not type 'string' or 'array'
	 * @param  string/array  $url url/query to identify
	 * @return string       environment name if true or else false
	 */
	public function identify_environment($url){
		if($this->_reg_urls === null) $this->_reg_urls = json_decode(file_get_contents(self::url_dir),true);
		if(is_array($url)){
			foreach ($url as $key => $value) $url[$key] = str_replace(["?","&"],"",$value);
			$query = false;
			foreach ($this->_reg_urls["query"] as $q)
				if(sizeof(array_intersect($q["queries"],$url)) == sizeof($url))
					return $q["environment"];
			return false;
		}
		else if(is_string($url)){
			$url = str_replace(\ENVIRONMENT::url()->root(false),"",$url);
			return isset($this->_reg_urls["url"][$url])?$this->_reg_urls["url"][$url]:false;
		}
		else throw new \InvalidArgumentException("Argument #1 must be an instance of 'string', but '".gettype($url)."' given", 1);
	}
	/**
	 * Register new url/query to be prioritized to the caller environment
	 * NOTE : throws exception if the url is already registered to another environment.
	 * It is wise to either check the availablity before or implement the following in try cache/
	 * @api
	 * @maintainance 'register-urlurl
	 * @maintainance 'register-url'
	 * @maintainance 'register-query'
	 * @throws InvalidArgumentException if $environment not type 'string'
	 * @throws InvalidArgumentException if $url not type 'string' or 'array'
	 * @throws \maple\environment\exceptions\UrlAlreadyRegisteredException if $url is already registered to another environment other than $environment
	 * @param  string $environment caller environment
	 * @param  string/array $url         url/query to register
	 * @return null
	 */
	public function register($environment,$url){
		if(!is_string($environment))	throw new \InvalidArgumentException("Argument 1 must be an instance of 'string', but '".gettype($environment)."' given", 1);
		if(is_string($url)){
			if(!$this->available($environment,$url) && $this->identify_environment($url) != $environment)
				throw new \maple\environment\exceptions\UrlAlreadyRegisteredException($url, 1);
			$url = str_replace(\ENVIRONMENT::url()->root(false),"",$url);
			$url = rtrim($url,"/");
			$this->_reg_urls["url"][$url] = $environment;
			\ENVIRONMENT::lock("maple/environment : register-url");
				file_put_contents(self::url_dir,json_encode($this->_reg_urls,JSON_PRETTY_PRINT));
			\ENVIRONMENT::unlock();
		}
		else if(is_array($url)){
			if(!$this->available($environment,$url) && $this->identify_environment($url) != $environment)
				throw new \maple\environment\exceptions\UrlAlreadyRegisteredException($url, 1);
			foreach ($url as $key => $value) $url[$key] = str_replace(["?","&"],"",$value);
			$query = [
				"queries"	=> array_values($url),
				"environment"=>	$environment
			];
			$this->_reg_urls["query"][] = $query;
			\ENVIRONMENT::lock("maple/environment : register-query");
				file_put_contents(self::url_dir,json_encode($this->_reg_urls,JSON_PRETTY_PRINT));
			\ENVIRONMENT::unlock();
		}
		else throw new \InvalidArgumentException("Argument 2 passed to ".__METHOD__." must be an instance of 'string' or 'array', ".gettype($url)." given", 1);
	}
	/**
	 * Unregister existing url/query belonging to the caller environment
	 * NOTE : throws exception if the url is not already registered or not registered to the current url.
	 * It is wise to either check the availablity before or implement the following in try cache/
	 * @api
	 * @maintainance 'unregister-url'
	 * @maintainance 'unregister-query'
	 * @throws InvalidArgumentException if $environment not type 'string'
	 * @throws InvalidArgumentException if $url not type 'string' or 'array'
	 * @throws \maple\environment\exceptions\UrlNotRegisteredException if $url is not already registered to current environment i.e. $environment
	 * @param  string $environment caller environment
	 * @param  string/array $url         url/query to register
	 * @return null
	 */
	public function unregister($environment,$url) {
		if(!is_string($environment))	throw new \InvalidArgumentException("Argument 1 passed to ".__METHOD__." must be an instance of string, ".gettype($environment)." given", 1);
		if(is_string($url)){
			$url = str_replace(\ENVIRONMENT::url()->root(false),"",$url);
			$url = rtrim($url,"/");
			if(!$this->available($environment,$url) && $this->identify_environment($url) != $environment)
				throw new \maple\environment\exceptions\UrlNotRegisteredException($url, 1);
			unset($this->_reg_urls["url"][$url]);
			\ENVIRONMENT::lock("maple/environment : unregister-url");
				file_put_contents(self::url_dir,json_encode($this->_reg_urls,JSON_PRETTY_PRINT));
			\ENVIRONMENT::unlock();
		}
		else if (is_array($url)) {
			if(!$this->available($environment,$url) && $this->identify_environment($url) != $environment)
				throw new \maple\environment\exceptions\UrlNotRegisteredException($url, 1);
			foreach ($url as $key => $value) $url[$key] = str_replace(["?","&"],"",$value);
			$query = [
				"queries" =>	$url,
				"environment"	=>	$environment
			];
			$query = array_search($query,$this->_reg_urls["query"]);
			if($query===false) return;
			unset($this->_reg_urls["query"][$query]);
			\ENVIRONMENT::lock("maple/environment : unregister-query");
				file_put_contents(self::url_dir,json_encode($this->_reg_urls,JSON_PRETTY_PRINT));
			\ENVIRONMENT::unlock();
		}
		else throw new \InvalidArgumentException("Argument 2 passed to ".__METHOD__." must be an instance of 'string' or 'array', ".gettype($url)." given", 1);
	}
	/**
	 * dumps the internal contents of the url.
	 * NOTE : only returns data when DEBUG is true
	 */
	public function dump(){
		if(\DEBUG){
			var_dump(
				$this->_content,
				$this->_reg_urls,
				self::url_dir
			);
		}
	}

}

/**
 * Basic File functionality
 * @package Maple Environment
 * @since 1.0
 */
class FILE{
	/**
	 * Saves Basic file details
	 * @var array
	 */
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
	];

	/**
	 * Static method to return mime type of file based on extension
	 * @uses finfo_open if cannot handle itself
	 * @param  string $filename file name
	 * @return string           mime-type
	 */
	public static function mime_type($filename) {
		$mime_types = [
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',
			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',
			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',
			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',
			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',
			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		];
		$f = explode('.',$filename);
		$ext = strtolower(array_pop($f));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}
		elseif (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
			return $mimetype;
		}
		else {
			return 'application/octet-stream';
		}
	}
	/**
	 * @uses self::mime_type to identify mime type of file
	 * @param string $file file path
	 */
	function __construct($file){
		$this->details["exists"] = file_exists($file);
		$data   = pathinfo($file);
		$this->details["name"] 		= $data["filename"];
		$this->details["basename"] 	= $data["basename"];
		$this->details["location"] 	= $file;
		$this->details["directory"] = $data["dirname"];
		$this->details["extension"] = isset($data["extension"])?$data["extension"]:"none";
		$this->details["mime"] 		= FILE::mime_type($file);
		$this->details["size"] 		= filesize($file);

		if($this->details["exists"]){
			$this->details["permission"]= fileowner($file);
			$this->details["owner"] 	= fileperms($file);
			$this->details["last-access-time"]= fileatime($file);
			$this->details["modify-time"]= filemtime($file);
		}
	}
	/**
	 * @return string file name
	 */
	public function name()			{ return $this->details["name"];  }
	/**
	 * @return string file base name
	 */
	public function basename()		{ return $this->details["basename"];  }
	/**
	 * @return string file location
	 */
	public function location()		{ return $this->details["location"];  }
	/**
	 * @return string file folder name
	 */
	public function directory()		{ return $this->details["directory"];  }
	/**
	 * @return string file extension
	 */
	public function extension()		{ return $this->details["extension"];  }
	/**
	 * @return string file mime type
	 */
	public function mime()			{ return $this->details["mime"];  }
	/**
	 * @uses human_filesize() to show human readable filesize
	 * @param bool $humanReadable set true to make file size readable for homo sapiens and aliens also
	 * @return string file size
	 */
	public function size($x = false){
		if($x) return \human_filesize($this->details["location"]);
		return $this->details["size"];
	}
	/**
	 * @return string file exists
	 */
	public function exists()		{ return $this->details["exists"];  }
	/**
	 * // BUG: Not testing for all file permission
	 * @param array $param file permission to check for
	 * @return string/false file permission / status
	 */
	public function permission($param = null)	{
		if(is_array($param)){
			foreach ($param as $value) {
			}
		}
		else if (is_string($param)){
			switch ($param) {
				case 'w': return is_writable($this->details["location"]); break;
				case 'r': return is_readable($this->details["location"]); break;
				default : throw new \DomainException("'{$param}' could not be processed for file permission", 1);
			}
		}
		if($param === null) return $this->details["permission"];
		else throw new \DomainException("Argument #1 should be of type 'array' or 'string' but '".gettype($param)."' passed", 1);
	}
	/**
	 * Attemts to change file permission
	 * @param integer permission
	 * @return mixed result
	 */
	public function grant($param){
		try{
			return chmod($this->details["location"],octdec($param));
		}catch(\Exception $e){
			return false;
		}
	}
	/**
	 * @return string file owner
	 */
	public function owner()			{ return $this->details["owner"];  }
	/**
	 * @return string last access time of file
	 */
	public function last_access()	{ return $this->details["last-access-time"];  }
	/**
	 * @return string last modification time
	 */
	public function last_modified()	{ return $this->details["modify-time"];  }
	/**
	 * Copy current file to another location
	 * @param  string $destination file destination
	 * @return bool              result
	 */
	public function copy($destination){
		return copy($this->details["location"],$destination);
	}
	/**
	 * Delete this file
	 */
	public function delete(){
		unlink($this->details["location"]);
	}
	/**
	 * Rename this file to another
	 * NOTE : You will not be able to change the working directory of the file
	 * @param  string $name New Name
	 * @return bool       result
	 */
	public function rename($name){
		// BUG: allows relocation of file to another place. remove slashes.
		// BUG: does not check for existing file
		return rename($this->details["location"],$this->details["directory"]."/$name");
	}
	/**
	 * Set Permissions to current file
	 * @param string $permission new permission
	 * @return bool result
	 */
	public function set_permission($permission){
		if(!is_string($environment)) throw new \DomainException("Argument #1 must be an instance of string, ".gettype($url)." passed", 1);
		// BUG: exceptions not cached
		return chmod($this->details["location"], $permission);
	}
	/**
	 * @return string read all contents
	 */
	public function read(){
		return file_get_contents($this->details["location"]);
	}
	/**
	 * Write any form of data to file.
	 * If data is not string type then it will be conerted to JSON.
	 * @param mixed $data contents for file
	 * @return bool result
	 */
	public function write($data){
		if( !$this->exists() && !file_exists($this->directory()) ){
			mkdir($this->directory());
		}
		if(is_array($data)) $data = json_encode($data);
		return file_put_contents($this->details["location"], $data);
	}
}


require_once __DIR__."/class-environment.php";
?>
