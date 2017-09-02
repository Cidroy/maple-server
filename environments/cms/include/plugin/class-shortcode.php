<?php
namespace maple\cms;

/**
 * Shortcode Handler
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class SHORTCODE {

	/**
	 * @access private
	 * @see Regex101 reference: https://regex101.com/r/pJ7lO1
	 */
	const SHORTOCODE_REGEXP = "/(?P<shortcode>(?:(?:\\s?\\[))(?P<name>[\\w\\-]{3,})(?:\\s(?P<attrs>[\\w\\d,\\s=\\\"\\'\\-\\+\\#\\%\\!\\~\\`\\&\\.\\s\\:\\/\\?\\|]+))?(?:\\])(?:(?P<content>[\\w\\d\\,\\!\\@\\#\\$\\%\\^\\&\\*\\(\\\\)\\s\\=\\\"\\'\\-\\+\\&\\.\\s\\:\\/\\?\\|\\<\\>]+)(?:\\[\\/[\\w\\-\\_]+\\]))?)/u";

	/**
	 * @access private
	 * @see Regex101 reference: https://regex101.com/r/sZ7wP0
	 */
	const ATTRIBUTE_REGEXP = "/(?<name>\\S+)=[\"']?(?P<value>(?:.(?![\"']?\\s+(?:\\S+)=|[>\"']))+.)[\"']?/u";

	/**
	 * Shortcode details
	 * @var array
	 */
	private $_property = [
		"name"		=>	false,
		"content"	=>	false,
		"parameters"	=> [],
	];

	/**
	 * String format buffer of shortcode
	 * @var string
	 */
	private $_str = null;

	/**
	 * Just a constructor doing constructor stuff
	 * @param string $name        shortcode name
	 * @param array $parameters   parameters
	 * @param string $content     primary value
	 */
	function __construct($name,$parameters = [],$content = ""){
		$this->_property["name"]	= $name;
		$this->_property["content"]	= $content;
		$this->_property["parameters"]= $parameters;
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
	 * Convert to string
	 * [shortcode param="value"]
	 * @return string
	 */
	public function __toString(){
		if($this->_str===null){
			$shortcode = [];
			$shortcode[] = $this->name;
			foreach ($this->parameters as $param => $value) {
				$value = is_string($value)?"{$value}":"";
				$shortcode[] = "$param=\"{$value}\"";
			}
			$shortcode = implode(" ",$shortcode);
			$this->_str = "[{$shortcode}]";
			if($this->content) $this->_str = $this->_str.$this->content."[/{$this->name}]";
		}
		return $this->_str;
	}

	/**
	 * Do Shortcode
	 * BUG: does nothing
	 * @api
	 * @return string output
	 */
	public function execute(){
		$output = "";
		ob_start();
		$function = MAPLE::sc_function($this);
		if(!$function) return $this->content;
		$output = call_user_func($function,$this->content,$this->parameters);
		ob_end_clean();
		return $output;
	}

	/**
	 * Returns Array of Shortcodes found in content
	 * @api
	 * @throws \InvalidArgumentException if $content not of type 'string'
	 * @param  string $content content
	 * @return array          shorcodes
	 */
	public static function parse($content){
		if(!is_string($content)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);

		$matches = [];
		preg_match_all(self::SHORTOCODE_REGEXP, $content, $matches, PREG_SET_ORDER);
		$shortcodes = [];
		foreach ($matches as $value) {
			$attrs = [];
			$content = "";
			if(isset($value["attrs"])){
				$attr_matches = [];
				preg_match_all(self::ATTRIBUTE_REGEXP, $value["attrs"], $attr_matches, PREG_SET_ORDER);
				foreach ($attr_matches as $attr) $attrs[$attr["name"]] = $attr["value"];
			}
			if(isset($value["content"])) $content = $value["content"];
			$shortcodes[] = new SHORTCODE($value["name"],$attrs,$content);
		}
		return $shortcodes;
	}

	/**
	 * Execute all the shortcodes in content
	 * @api
	 * @throws \InvalidArgumentException if $content is not of type 'string'
	 * @param  string $content content
	 * @return string          parsed content
	 */
	public static function execute_all($content){
		if(!is_string($content)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);

		$shortcodes = self::parse($content);
		foreach ($shortcodes as $shortcode)
		 	$content = substr_replace($content,$shortcode->execute(),strpos($content,(string)$shortcode),strlen((string)$shortcode));
		return $content;
	}

}

?>
