<?php
namespace maple\environment\exceptions;

/**
 * if url is registered with the maple environment for another environment
 */
class UrlAlreadyRegisteredException extends \Exception{
	public function __construct($message,$code = 0,\Exception $previous = null) {
		if(is_array($message)) $message = implode(",",$message);
		$message = "Error on line {$this->getLine()} in {$this->getFile()} : '{$message}' is already registered to another environment";
		parent::__construct($message,$code,$previous);
	}
}

/**
 * if url is not registered with any environment
 */
class UrlNotRegisteredException extends \Exception{
	public function __construct($message,$code = 0,\Exception $previous = null) {
		if(is_array($message)) $message = implode(",",$message);
		$message = "Error on line {$this->getLine()} in {$this->getFile()} : '{$message}' is not registered";
		parent::__construct($message,$code,$previous);
	}
}

/**
 * if an environment is not installed on the current server
 */
class InvalidEnvironmentException extends \Exception{
	public function __construct($message,$code = 0,\Exception $previous = null) {
		if(is_array($message)) $message = implode(",",$message);
		$message = "Error on line {$this->getLine()} in {$this->getFile()} : '{$message}' is not a valid environments";
		parent::__construct($message,$code,$previous);
	}
}

?>
