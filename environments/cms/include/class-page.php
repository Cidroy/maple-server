<?php
namespace maple\cms;

/**
 * Page Handler for Maple
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class PAGE{
	const page_identifiers = ["url","id","title","content","name"];

	/**
	 * Return the nearest matching url
	 * BUG: does nothing
	 * @api
	 * @throws \InvalidArgumentException if $url is not of type 'string'
	 * @param  string $url url to match for
	 * @return string      nearest matching url
	 */
	public static function identify($url){
		if(!is_string($url)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		$url = str_replace(URL::http("%ROOT%"),"",$url);
		return $url;
	}

	/**
	 * Get details of page
	 * @api
	 * @throws \InvalidArgumentException if $identifier is not of type 'string'
	 * @throws \InvalidArgumentException if $value is not of type 'string'
	 * @throws \DomainException if $identifier is not a valid value
	 * @param  string $identifier identifier name
	 * @param  string $value value for identifier
	 * @return array             details
	 */
	public static function get($identifier,$value){
		if(!is_string($identifier))	throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_string($value))	throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);
		if(!in_array($identifier,self::page_identifiers)) throw new \DomainException("Invalid Argument #1", 1);
		return current(DB::_()->select("pages","*",[$identifier => $value]));
	}

	/**
	 * Add a new Page
	 * BUG: does nothing
	 * @api
	 * @permission maple/cms:page|add
	 * @filter page|added
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if permission not available
	 * @throws \InvalidArgumentException if $details is not of type 'array'
	 * @throws \DomainException if $details does not contain all required data
	 * @param array $details details
	 * @return integer page
	 * returns false if url is unavailable
	 */
	public static function add($details){
		if(!is_array($details)) throw new \InvalidArgumentException("Argument #1 must be of type 'array'", 1);
		if(array_diff(array_diff(self::page_identifiers,["id"]),array_keys($details))) throw new \DomainException("Invalid Argument #1", 1);
		if(!SECURITY::permission("maple/cms","page|add")) throw new \maple\cms\exceptions\InsufficientPermissionException("", 1);
		if(!\ENVIRONMENT::url()->available("maple/cms",$details["url"])) return false;
		if(DB::_()->has("pages",[
			"OR"	=>	[
				"name"	=>	$details["name"],
				"url"	=>	$details["url"],
			]
		])) return false;

		$details["#created"] = "NOW()";
		$details["author"]	 = USER::id();
		$id = DB::_()->insert("pages",$details);
		\ENVIRONMENT::url()->register("maple/cms",$details["url"]);
		MAPLE::do_filters("page|added",["page-id" => $id]);
		return $id;
	}

	/**
	 * Edit Page
	 * BUG: does nothing
	 * @api
	 * @permission maple/cms:page|edit
	 * @filter page|edited
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if permission not available
	 * @throws InvalidArgumentException if $id is not of type 'integer'
	 * @throws \InvalidArgumentException if $details is not of type 'array'
	 * @throws \Exception if $details is creates issues
	 * @param  integer $id      page id
	 * @param  array $details details
	 * @return boolean status
	 * returns 0 for invalid id
	 * returns false if $details["url"] is not available
	 */
	public static function edit($id,$details){
		if(!is_integer($id)) throw new \InvalidArgumentException("Argument #1 must be of type 'integer'", 1);
		if(!is_array($details)) throw new \InvalidArgumentException("Argument #2 must be of type 'array'", 1);
		if(!SECURITY::permission("maple/cms","page|edit")) throw new \maple\cms\exceptions\InsufficientPermissionException("", 1);

		$prev_details = self::get("id",$id);
		if(!$prev_details) return false;

		if(isset($details["url"]) && $details["url"] != $prev_details["url"]){
			if(!\ENVIRONMENT::url()->available("maple/cms",$details["url"])) return false;
			\ENVIRONMENT::url()->unregister("maple/cms",$prev_details["url"]);
			\ENVIRONMENT::url()->register("maple/cms",$details["url"]);
		}
		MAPLE::do_filters("page|edited",["page-id" => $id]);
		return true;
	}

	/**
	 * delete a page
	 * BUG: does nothing
	 * @api
	 * @permission maple/cms:page|delete
	 * @filter page|deleted
	 * @throws \maple\cms\exceptions\InsufficientPermissionException if permission not available
	 * @throws InvalidArgumentException if $id is not of type 'integer'
	 * @param  integer $id page id
	 * @return boolean status
	 */
	public static function delete($id){
		if(!is_integer($id)) throw new \InvalidArgumentException("Argument #1 must be of type 'integer'", 1);
		if(!SECURITY::permission("maple/cms","page|delete")) throw new \maple\cms\exceptions\InsufficientPermissionException("", 1);

		if(!self::get("id",$id)) return false;

		\ENVIRONMENT::url()->unregister("maple/cms",$details["url"]);
		MAPLE::do_filters("page|deleted",["page-id" => $id]);
		return true;
	}
}

?>
