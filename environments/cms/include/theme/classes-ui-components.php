<?php
namespace maple\cms\ui\components;
/**
 * Navbar Object
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class __navbar{
	/**
	 * Stores details
	 * @var array
	 */
	private $_content = [
		"buttons"	=>	[],
		"links"	=>	[],
		"html"		=>	"",
	];
	/**
	 * Add Button to navbar
	 * @param string $name button text
	 * @param string $link url
	 * @param array  $args additional parameters
	 */
	public function add_button($name,$link = "#",$args=[]) { $this->_content["buttons"][$name] = ["link" => $link,"args"=>$args]; }
	/**
	 * Add a hyper link
	 * @param string $name button text
	 * @param string $link url
	 * @param string $icon icon name
	 * @param array  $args additional parameters
	 */
	public function add_link($name,$link,$icon = "",$args=[]) { $this->_content["links"][$name] = ["link" => $link,"icon"=>$icon,"args"=>$args]; }
	/**
	 * Add html to navbar
	 * @param string $html html
	 */
	public function add_html($html){ $this->_content["html"] .= $html; }
	/**
	 * Get buttons
	 * @return array buttons
	 */
	public function buttons(){ return $this->_content["buttons"]; }
	/**
	* Get links
	* @return array links
	*/
	public function links(){ return $this->_content["links"]; }
	/**
	* Get html
	* @return array html
	*/
	public function html(){ return $this->_content["html"]; }
}
/**
 * Title Object
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class __title{
	/**
	 * Titles List
	 * @var array string
	 */
	private $src = [];
	/**
	 * Add a title
	 * @param string $src title
	 */
	public function add($src){ $this->src[] = $src; }
	/**
	 * Return title
	 * @return array string titles
	 */
	public function get(){ return $this->src; }
}

/**
 * Links Object
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class __links{
	/**
	 * Sources
	 * @var array string
	 */
	private $src = [];
	/**
	 * Style
	 * @var string
	 */
	private $code = "";
	/**
	 * Add Sources
	 * @param string $src url
	 */
	public function add_src($src){
		$src = \URL::http($src);
		$this->src[] = $src;
	}
	/**
	 * Get Sources
	 * @return array string sources
	 */
	public function src(){ return $this->src; }
	/**
	 * Add Script
	 * NOTE : Do not put html tag, directly write the css
	 * @param string $code style
	 */
	public function add($code){ $this->code .= $code; }
	/**
	 * Get scripts
	 * @return string scripts
	 */
	public function get_script(){ return $this->code; }
}

/**
 * html Object
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class __html{
	/**
	 * html codes
	 * @var string
	 */
	private $code = "";
	/**
	 * Add html codes
	 * @param string $code html
	 */
	public function add($code){ $this->code .= $code; }
	/**
	 * Get html
	 * @return string html
	 */
	public function get(){ return $this->code; }
}
?>
