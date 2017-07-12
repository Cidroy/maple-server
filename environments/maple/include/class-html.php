<?php
/**
* This is the HTML class that allows your plugin to seamlesly create custom input element,
* provides functionality for Template Editind
* @package Maple Framework
*/
class HTML{
	private static $__loaded_table_data = false;
	private static $__loaded_graph_data = false;
	private static $__loaded_list = false;
	private static $__graph_no			= 0;

	public static function Input($name,$params=false){
		$input = "<input name=\"{{Name}}\" {{Param}}>";
		$param = '';
		if(is_array($params)) foreach ($params as $key => $value) $param .= "$key = \"$value\" ";
		return str_replace(array("{{Name}}","{{Param}}"),array($name,$param),$input);
	}

	public static function AjaxInput($class,$action){
		return HTML::Input('maple_ajax',array("hidden"=>'','value'=>$class)).
		HTML::Input('maple_ajax_action',array("hidden"=>'','value'=>$action));
	}

	public static function Textbox($name,$params=false){
		$textbox = "<textarea name='$name' {{Param}}>{{Content}}</textarea>";
		$param = '';
		$content = '';
		$params['class'] = isset($params['class'])?$params['class']." advanced-textbox":" advanced-textbox";
		if(is_array($params)){
			foreach ($params as $key => $value){
				if($key == 'content') $content = $value;
				else $param .= "$key = \"$value\" ";
			}
		}
		MAPLE::do_filters('advanced_textbox');
		return str_replace(array("{{Name}}","{{Param}}","{{Content}}"),array($name,$param,$content),$textbox);
	}

	public static function Button($params=false){
		$button = "<button {{Param}}>{{Content}}</button>";
		$param = '';
		$content = '';
		if(is_array($params)){
			foreach ($params as $key => $value){
				if($key == 'content') $content = $value;
				else $param .= "$key = \"$value\" ";
			}
		}
		return str_replace(array("{{Param}}","{{Content}}"),array($param,$content),$button);
	}

	public static function Select($params=false){
		$textbox = "<select {{Param}}>{{Content}}</select>";
		$param = '';
		$content = '';
		if(is_array($params)){
			if(isset($params['options']))
				foreach ($params['options'] as $value) $content .= "<option >$value</option>";
			foreach ($params as $key => $value) if(!in_array($key,array('options'))) $param .= "$key = \"$value\" ";
		}
		return str_replace(array("{{Param}}","{{Content}}"),array($param,$content),$textbox);
	}

	public static function MultiSelect($params=false){
		$textbox = "<select class=\"{{Class-Textarea}}\" {{Param}}>{{Content}}</select>";
		$param = '';
		$content = '';
		if(is_array($params)){
			if(isset($params['options']))
				foreach ($params['options'] as $key => $value) $content .= "<option value='$key'>$value</option>";
			foreach ($params as $key => $value) if(!in_array($key,array('options'))) $param .= "$key = \"$value\" ";
		}
		return str_replace(array("{{Name}}","{{Param}}","{{Content}}","{{Class-Textarea}}"),array($name,$param,$content,(isset($params['class']))?$params['class']:''),$textbox);
	}

	public static function Switcher($params=false){
		$textbox = <<<EOT
			<label class="switch {{Class-Textarea}}">
				<input type="checkbox" {{Param}}>
				<span class="check"></span>
			</label>
EOT;
		$param = '';
		$content = '';
		if(is_array($params))
			foreach ($params as $key => $value) $param .= "$key = \"$value\" ";
		return str_replace(array("{{Param}}","{{Content}}","{{Class-Textarea}}"),array($param,$content,(isset($params['class']))?$params['class']:''),$textbox);
	}

	public static function TagInput($name){
		return "TODO : Tag Input helper";
	}

	public static function DataTable($tablename){
		if(!HTML::$__loaded_table_data){
			UI::css()->add_src(HTML::CSSFile('datatable','main'));
			UI::js()->add_src(HTML::JSFile('datatable','main'));
			HTML::$__loaded_table_data = true;
		}
		$Template ="<script>$('{$tablename}').DataTable();</script>";
		UI::footer()->add($Template);
	}

	public static function DataList($listname,$param = array()){
		static $i = 0;
		if(!HTML::$__loaded_list){
			UI::css()->add_src(HTML::CSSFile('datatable','list'));
			UI::js()->add_src(HTML::JSFile('datatable','list'));
			HTML::$__loaded_list = true;
		}
		$Template = json_encode($param);
		$Template = "<script> var ulist{$i} = new List('{$listname}',{$Template}) </script>";
		UI::footer()->add($Template);
	}

	public static function Graph($param){
		if(!HTML::$__loaded_graph_data){
			UI::js()->add_src("https://www.gstatic.com/charts/loader.js");
			UI::footer()->add("<script>google.charts.load('current', {packages: ['corechart']});</script>");
			HTML::$__loaded_graph_data = true;
		}
		array_merge($param,array(
			'size'	=>	array( 'width'=>'',	'height'=>'100%' ),
			'data'	=>	'',
			'type'	=>	false,
		));
		if(!in_array($param['type'],array('PieChart','AreaChart','ColumnChart','LineChart'))){
			Log::debug('Invalid Graph Type',$param);
			return false;
		};
		HTML::$__graph_no += 1;
		UI::footer()->add(TEMPLATE::Render('maple',"graph/generic",array(
			'data'	=>	array(
				'options'	=>	json_encode($param['data']['options']),
				'values'	=>	json_encode($param['data']['values']),
			),
			'id'	=>	HTML::$__graph_no,
			'type'	=>	$param['type']
		)));
		return "<div class='valign-wrapper' id='maple-chart-".HTML::$__graph_no."' style='width: {$param['size']['width']}; height: {$param['size']['height']};'><div class='valign center-align preloader-wrapper big active' style='margin:auto'><div class='spinner-layer spinner-blue-only'><div class='circle-clipper left'><div class='circle'></div></div><div class='gap-patch'><div class='circle'></div></div><div class='circle-clipper right'><div class='circle'></div></div></div></div> </div>";
	}

	public static function CSSFile($owner,$file){ return URL::http("%ROOT%%DATA%css/{$owner}/{$file}.css"); }
	public static function JSFile($owner,$file){ return URL::http("%ROOT%%DATA%js/{$owner}/{$file}.js"); }
}
?>
