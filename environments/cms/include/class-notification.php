<?php
namespace maple\cms;

/**
 * Notification Style
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class NOTIFICATION_STYLE{
	const Plain = 0;
	const BigPicture = 1;
	const Html = 2;
}

/**
 * Notification Handler
 * A Notification should have the following properties
 * - title : Title of notification
 * - text : a PLain text descsription.
 * - style : notification design style using 'NOTIFICATION_STYLE'. defaults to 'Plain'
 * NOTE : all html tags will be remoed from text. Although '\n' can be used for new line.
 * Additionally the following properties can be defined
 * - link : url redirect for onClick event
 * - icon : url or data:\\
 * - number : bundling
 * - expiry : when to expire the notification
 * - html : if cusstom notification style
 * - picture : if notification style is BigPicture
 * - summary_text : a last line summery text
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class NOTIFICATION{
	/**
	 * Application Source Namespace of the notification
	 * @var string
	 */
	private $_namespace = false;
	/**
	 * Unique Notification Id
	 * @var integer
	 */
	private $_id = false;

	/**
	 * Notification Properties
	 * @var array
	 */
	private $_property = [
		"title"	=>	false,
		"text"	=>	false,
		"icon"	=>	false,
		"number"=>	false,
		"expire"=>	false,
		"link"	=>	false,
		"style"	=>	false,
		"html"	=>	false,
		"once"	=>	true,
		"picture"=>	false,
		"summary_text"	=>	false,
	];

	/**
	 * save seen status
	 * @var boolean
	 */
	private $_seen = false;

	/**
	 * Just a constructor
	 * @uses PLUGIN::active
	 * @throws \InvalidArgumentException if $namespace not of type 'string'
	 * @param string $namespace Plugin Namespace
	 */
	function __construct($namespace,$id = false){
		if(!is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!PLUGIN::active($namespace)) throw new \maple\cms\exceptions\InvalidPluginException("Argument #1 must be a valid plugin", 1);
		$this->_property["style"] = NOTIFICATION_STYLE::Plain;
		$this->_namespace = $namespace;
		if($id){
			$property = self::get($namespace,$id);
			if($property){
				if($property instanceof $this){ $this->copy($property); }
				else $this->_property = $property;
			}
			else $this->_id = $id;
		}
	}

	/**
	 * Clone this with object
	 * @api
	 * @throws \InvalidArgumentException if $object not an instatnce of 'Notification'
	 * @param  Notification $object object
	 */
	public function copy($object){
		if(!($object instanceof $this)) throw new \InvalidArgumentException("Argument #1 must be an instance of 'Notification'", 1);
		$this->_namespace = $object->_namespace;
		$this->_id = $object->_id;
		$this->_property = $object->_property;
		$this->_seen = $object->_seen;
	}

	/**
	 * Set Notification property
	 * @param string $name  Property Name
	 * @param mixed[] $value value
	 */
	public function __set($name,$value){
		if(isset($this->_property[$name])) $this->_property[$name] = $value;
		else throw new \DomainException("Invalid Property '$name'", 1);
	}

	/**
	 * Get Notification Properties
	 * @param  string $name property name
	 * @return mixed[]      value
	 */
	public function __get($name){
		if(isset($this->_property[$name])) return $this->_property[$name];
		else throw new \DomainException("Invalid Property '$name'", 1);
	}


	/**
	 * Push Notification to user
	 * @api
	 * @uses self::_notify
	 * @return integer notification id
	 */
	public function notify(){
		$temp = self::_notify($this);
		if(!$temp) try{ $this->copy(new NOTIFICATION($this->_namespace,$this)); }
		catch(\Exception $e){}
		return $temp;
	}

	/**
	 * Modify The Notification
	 * @api
	 * @uses self::_modify
	 * @throws \RuntimeException if $this->notify was not called previously
	 * @return boolean status
	 */
	public function modify(){
		if(!$this->_id)	throw new \RuntimeException("Notification must be notified before modification", 1);
		return self::_modify($this);
	}

	/**
	 * Return id
	 * @api
	 * @return string id
	 */
	public function id(){ return $this->_id;}

	/**
	 * Notification is seen by user
	 * @api
	 * @return boolean	status
	 */
	public function seen(){ return $this->_seen;}


	private static $_notifications = [];

	/**
	 * Dispatch Notification Object for notification
	 * BUG : getting cleared
	 * @uses CACHE::unique
	 * @param this $object	notification
	 * @return return notification id
	 * returns false if the notification is not unique
	 */
	private static function _notify($object){
		if(!isset(self::$_notifications[$object->_namespace]))
			self::$_notifications[$object->_namespace] = [];
		foreach (self::$_notifications[$object->_namespace] as $id => $value){
			if( ($value["title"] == $object->title) && ($value["text"] == $object->text) && ($value["style"] == $object->style) )
				return false;
		}
		$id = CACHE::unique().time().rand();
		self::$_notifications[$object->_namespace][$id] = array_merge($object->_property,[
			"time"	=>	time(),
		]);
		self::_save();
		return $id;
	}

	/**
	 * Modify a notification
	 * if notification not deployed then return false
	 * if notification is seen then return false
	 * @param  this $object notification
	 * @return boolean         status
	 */
	private static function _modify($object){
		if(!isset(self::$_notifications[$object->_namespace][$object->_id]) || $object->seen()) return false;
		self::$_notifications[$object->_namespace][$object->_id] = array_merge($object->_property,[
			"modified"	=>	time(),
		]);
		self::_save();
		return true;
	}

	/**
	 * Save the notification policies
	 */
	private static function _save(){
		if(USER::loggedin()) CACHE::put("maple/notification","notifications",self::$_notifications,["user-specific" => true]);
		else SESSION::set("maple/notification","notifications",self::$_notifications);
	}

	/**
	 * Initialize the notifications
	 * BUG : does not delete old notifications :(
	 */
	public static function initialize(){
		self::$_notifications = USER::loggedin()?
			CACHE::get("maple/notification","notifications",[],["user-specific" => true]):
			SESSION::get("maple/notification","notifications",[])
		;
		$buffer = [];
		foreach (self::$_notifications as $namespace => $notification) {
			$buffer[$namespace] = [];
			foreach ($notification as $id => $data) {
				// BUG: if time.now <= $data["expiry"]
				$buffer[$namespace][$id] = $data;
			}
		}
		self::$_notifications = $buffer;
		self::_save();
	}

	/**
	 * Set notification to seen
	 * BUG: Implement notification seen
	 * @param  string $namespace notification namespace
	 * @param  integer $id        notification id
	 * @return boolean            status
	 */
	public static function _seen($namespace,$id){
	}

	/**
	 * return all notifications in current session if no parameters is passed
	 * return notifications for plugin if only namespace is passed
	 * return notification for plugin if namespace and id is passed
	 * @api
	 * @param string $namespace plugin namespace
	 * @param string $id notification id
	 * @return array notifications
	 */
	public static function get($namespace = null, $id = null){
		if($namespace && !is_string($namespace)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if($namespace && $id){
			if($id instanceof NOTIFICATION){
				$object = $id;
				$id = "";
				foreach (self::$_notifications[$object->_namespace] as $id => $value){
					if( ($value["title"] == $object->title) && ($value["text"] == $object->text) && ($value["style"] == $object->style) ){
						$temp = new NOTIFICATION($namespace);
						$temp->_id = $id;
						$temp->_namespace = $namespace;
						$temp->_property = $value;
						return $temp;
					}
				}
				return false;
			}
			else if(is_string($id)) return isset(self::$_notifications[$namespace][$id])?self::$_notifications[$namespace][$id]:false;
			else throw new \InvalidArgumentException("Argument #2 must be an instance of 'Notification' or 'string'", 1);

		}
		if($namespace && $id===null) return isset(self::$_notifications[$namespace])?self::$_notifications[$namespace]:[];
		return self::$_notifications;
	}

	/**
	 * Clear all notifications from Plugin Notification
	 * @param  this $object notification object
	 * @return true         always
	 */
	public static function clear_all($object){
		unset(self::$_notifications[$object->_namespace]);
		// BUG: remove this
		self::$_notifications = [];
		self::_save();
		return true;
	}

	/**
	 * Clear notification
	 * @param  this $object notification object
	 * @return true         always
	 */
	public static function clear($object){
		unset(self::$_notifications[$object->_namespace][$object->_id]);
		self::_save();
		return true;
	}

}

NOTIFICATION::initialize();
?>
