<?php
/*	function __($args){
		// #VICKY [+ !] : make this the text conerter function.
		//Fatal error: Cannot redeclare __() (previously declared in C:\xampp\htdocs\mFramework\M-Include\functions.php:2) in C:\xampp\htdocs\mFramework\wp-includes\l10n.php on line 173
		return  $args;
	}
*/
	/**
	* This is used to write any kind of failure that may have occured due to some issues.
	* It can be used to store any data on the server hidden from client for reference
	* @param type : Name of Caller
	* @param args : parameters
	* @return file.write : writes a JSON data to a file in {@link ~$Debug/~$[md5(% SESSION.ID %)].txt }
	* @package Maple Framework
	*/
	function Debug($type,$args){
		if(DEBUG){
			$time=getdate();
			// TODO : Based on session name id!
			$data=$time['mday'].'-'.$time['month'].'-'.$time['year'];
			@$file= ROOT.'maple/~$Debug/~$'.$data.'.txt';
			// TODO : Change write style!
			$data=$time['mday'].'-'.$time['month'].'-'.$time['year'].' '.$time['hours'].':'.$time['minutes'].':'.$time['seconds'];
			$data = array(
				'time' => $data,
				'type' => $type,
				'args' => $args,
				'debug_backtrace' => debug_backtrace(),
			);
			$data = json_encode($data).",\n";
			file_put_contents($file,$data,FILE_APPEND|LOCK_EX);
		}
	}

	function EmptyFunction(){ }
?>
