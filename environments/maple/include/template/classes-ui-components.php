<?php
namespace maple\ui\components;
class __navbar{
	private $_content = [
		"buttons"	=>	[],
		"html"		=>	"",
	];

	public function add_button($name,$link,$args=[]) { $this->_content["buttons"][$name] = ["link" => $link,"args"=>$args]; }
	public function html(){ return $this->_content["html"]; }
	public function buttons(){ return $this->_content["buttons"]; }
	public function add_html($html){ $this->_content["html"] .= $html; }
}

class __title{
	private $src = [];

	public function add($src){ $this->src[] = $src; }
	public function get(){ return $this->src; }
}

class __CSS{
	private $src = [];
	private $code = "";

	public function add_src($src){
		$src = \URL::http($src);
		$this->src[] = $src;
	}
	public function src(){ return $this->src; }
	public function add($code){ $this->code .= $code; }
	public function get_script(){ return $this->code; }
}

class __JS{
	private $src = [];
	private $code = "";

	public function add_src($src){
		$src = \URL::http($src);
		$this->src[] = $src;
	}
	public function src(){ return $this->src; }
	public function add($code){ $this->code .= $code; }
	public function get_script(){ return $this->code; }
}

class __footer{
	private $src = [];
	private $code = "";

	public function add($code){ $this->code .= $code; }
	public function get_script(){ return $this->code; }
}

class __header{
	private $src = [];
	private $code = "";

	public function add($code){ $this->code .= $code; }
	public function get_script(){ return $this->code; }
}

class __content{
	private $src = [];
	private $priority_src = [];
	private $content_sorted = false;

	private static function sort($a,$b){ return $a["priority"]==$b["priority"]?0:($a["priority"]<$b["priority"]?1:-1); }

	public function add($func,$args,$priority=0,$count=true){
		$this->src[$func] = $args;
		$this->priority_src[] = [
			"priority"	=>	$priority,
			"function"	=>	$func,
			"count"	=>	$count
		];
		$this->content_sorted = false;
	}
	public function resolve(){
		ob_start();
		$data = \ROUTE::Dispatch();
		if($data["success"]){
			call_user_func($data["handler"] ,$data["parameters"]);
			\MAPLE::$content = true;
		};
		while ($this->priority_src) {
			$buffer = [];
			if(!$this->content_sorted){
				usort($this->priority_src,["\maple\ui\components\__content","sort"]);
				$this->content_sorted = true;
			}
			$this->priority_src = array_unique($this->priority_src,SORT_REGULAR);
			foreach ($this->priority_src as $key => $value) {
				$args = $this->src[$value["function"]];
				if($value["function"]) call_user_func($value["function"],$args);
				if($value['count']) \MAPLE::$content = true;
				\Log::debug($value["function"] , $args);
				array_push($buffer,$value);
				unset($this->src[$value["function"]]);
			}
			@$this->priority_src = array_diff($this->priority_src,$buffer);
		}
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}

class __language{
	private $value1 = "en";

	public function get(){ return $this->value1; }
	public function set($value){ $this->value1 = $value; }
}

class __search{
	private $src = [];

	public function add($src){ $this->src[] = $src; }
	public function search($str){
		$vals = [];
		foreach ($tihs->src as $engine) {
			$val_temp = [
				"icon"	=>	"",
				"title"	=>	"",
				"title"	=>	"",
				"content" => "",
				"link"	=>	"",
				"template"	=>	false/*[
					"author"	=>	"",
					"file"	=>	"",
				]*/
			];
			$vals_temp = call_user_func($engine,$str);
			if($vals_temp){
				foreach ($vals_temp as $result) {
					$result = array_merge($val_temp,$result);
					$vals[] = [
						"icon"	=>	$result["icon"],
						"title"	=>	$result["title"],
						"content"=>	$result["content"],
						"link"	=>	$result["link"],
						"html"	=>	$result["template"]?\TEMPLATE::render($result["template"]["author"],$result["template"]["file"]):false
					];
				}
			}
		}

		return $vals;
	}
}
?>
