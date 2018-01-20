<?php
namespace maple\cms;
use maple\cms\ui\components\__navbar;
use maple\cms\ui\components\__links;
use maple\cms\ui\components\__html;
use maple\cms\ui\components\__title;
require_once 'classes-ui-components.php';
require_once 'interface-ui.php';

/**
 * UI Commands
 * @since 1.0
 * @package Maple CMS
 * @author Rubixcode
 */
class UI implements iUI{
	private static $objs = [];
	private static $theme = null;

	private static $filters = [
		"content"	=>	[],
	];

	/**
	 * Initialize
	 * @uses \maple\cms\THEME::theme_class
	 */
	public static function initialize(){
		self::$objs["navbar"] = new __navbar();
		self::$objs["css"] = new __links();
		self::$objs["js"] = new __links();
		self::$objs["header"] = new __html();
		self::$objs["footer"] = new __html();
		self::$objs["title"] = new __title();

		self::$theme = THEME::theme_class();
	}

	public static function navbar() { return self::$objs["navbar"]; }
	public static function css(){ return self::$objs["css"]; }
	public static function js(){ return self::$objs["js"]; }
	public static function header() { return self::$objs["header"]; }
	public static function footer() { return self::$objs["footer"]; }
	public static function title() { return self::$objs["title"]; }

	/**
	 * Return html icon tag for icon
	 * @api
	 * @throws \InvalidArgumentException if $name not of type 'string'
	 * @param  string $name name
	 * @return string       html
	 */
	public static function icon($name){
		if(!self::$theme) return false;
		return call_user_func(self::$theme."::icon",$name);
	}

	/**
	 * Add Content Filter
	 * @api
	 * @param string  $function function name
	 * @param integer $priority functional priority
	 */
	public static function add_filter($function,$priority = 0){
		if(!is_string($function)) throw new \InvalidArgumentException("Argument #1 must be of type 'string'", 1);
		if(!is_integer($priority)) throw new \InvalidArgumentException("Argument #2 must be of type 'string'", 1);

		if(!isset(self::$filters["content"][$priority])) self::$filters["content"][$priority] = [];
		self::$filters["content"][$priority][] = $function;
	}

	/**
	 * Execute output filters
	 * @param  string $context context
	 * @return string          modified html
	 */
	public static function do_filters($context){
		$output = null;
		$previous = $context;
		while(self::$filters["content"]){
			reset(self::$filters["content"]);
			$priority = key(self::$filters["content"]);
			while (self::$filters["content"][$priority]) {
				reset(self::$filters["content"][$priority]);
				$hook = key(self::$filters["content"][$priority]);
				ob_start();
					$ret = call_user_func(self::$filters["content"][$priority][$hook],$previous);
					$previous["content"] = is_string($ret)?$ret:$previous["content"];
				ob_end_clean();
				unset(self::$filters["content"][$priority][$hook]);
			}
			unset(self::$filters["content"][$priority]);
		}
		return $previous["content"];
	}

	/**
	 * Graph
	 * @api
	 * @uses Google Charts
	 * @param  array $param {
	 *         @type string "type" graph type
	 *         @type array "size" graph size
	 *         @type array "data" {
	 *               @type array "options" options
	 *               @type array "values" values
	 *         }
	 * }
	 * @return string        graph container
	 */
	public static function graph($param){
		static $__graph_init = false;
		static $__graph_no = 0;
		static $__graph_identifier = "maple-chart-";
		if($__graph_init === false){
			self::js()->add_src("https://www.gstatic.com/charts/loader.js");
			self::js()->add("google.charts.load('current', {packages: ['corechart']});");
			$__graph_init = true;
		}
		$suportedGraphs =  ['PieChart','AreaChart','ColumnChart','LineChart'];
		$param = array_merge([
			"size"	=>	["width" => "","height"=>"100%"],
			'data'	=>	'',
			'type'	=>	false,
		],$param);
		if(!in_array($param["type"],$suportedGraphs)){ LOG::error("Unsupported Graph type '{$param["type"]}'"); return false; }
		$__graph_no++;
		self::js()->add(TEMPLATE::render("maple","graph/js-generic",[
			"data"	=>	[
				'options'	=>	$param['data']['options'],
				'values'	=>	array_values($param['data']['values']),
			],
			"id"	=>	$__graph_identifier.$__graph_no,
			"type"	=>	$param["type"]
		]));
		return TEMPLATE::render("maple","graph/html",[
			"size"	=>	$param["size"],
			"id"	=>	$__graph_identifier.$__graph_no,
		]);
	}

	public static function datatable($id = null,$param = [""=>""]){
		static $__id = 0;
		static $__identifier = "#datatable-";
		static $__loaded = false;
		if($__loaded===false){
			self::js()->add_src(PLUGIN::path("maple/cms")."/assets/js/datatable.js");
			self::css()->add_src(PLUGIN::path("maple/cms")."/assets/css/datatable.css");
			$__loaded = true;
		}
		if(!is_array($param)) $param = [$param];
		$param = json_encode($param);
		if($id===null){ $id = $__identifier.$__id; $__id++;}
		self::js()->add("$('{$id}').DataTable({$param});");
		return "$id";
	}
}

UI::initialize();
?>
