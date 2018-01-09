<?php
/**
* This the mail class that does every mail related task for you.
* @package Maple Framework
*/
class MAIL{
	public static function Send($to,$subject,$body){
		Log::debug("MAIL FAILED",['to'=>$to,'subject'=>$subject,'body'=>$body]);
	}
}
?>
