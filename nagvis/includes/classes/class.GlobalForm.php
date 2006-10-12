<?php
/**
 * Class for managing the common form
 * Should be used by ALL pages of NagVis and NagVisWui where forms are used
 *
 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
 */
class GlobalForm {
	var $name;
	var $id;
	var $method;
	var $action;
	var $onSubmit;
	var $cols;
	var $enctype;
	
	/**
	 * Class Constructor
	 *
	 * @param 	Array	$prop	Array('name'=>'myform','id'=>'myform','method'=>'POST','action'=>'','onSubmit'=>'','cols'=>'2','enctype'=>''
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function GlobalForm($prop=Array('name'=>'myform','id'=>'myform','method'=>'POST','action'=>'','onSubmit'=>'','cols'=>'2','enctype'=>'')) {
		$this->name = $prop['name'];
		$this->id = $prop['id'];
		$this->method = $prop['method'];
		$this->action = $prop['action'];
		$this->onSubmit = $prop['onSubmit'];
		$this->cols = $prop['cols'];
		$this->enctype = $prop['enctype'];
	}
	
	/**
	 * Gets a hidden field
	 *
	 * @param 	String 	$name 	Name of the Field
	 * @param 	String 	$value 	Value of the Field
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getHiddenField($name,$value) {
		$ret = Array();
		
		$ret[] = "<input type=\"hidden\" name=\"".$name."\" value=\"".$value."\" />";
		
		return $ret;
	}
	
	/**
	 * Gets a title row
	 *
	 * @param 	String 	$title 	Name of the title
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getTitleLine($title) {
		$ret = Array();
		
		$ret[] = "<tr>";
		$ret[] = "<td class=\"tdtitle\" colspan=\"".$this->cols."\">".$title."</td>";
		$ret[] = "</tr>";
		
		return $ret;
	}
	
	/**
	 * Gets a category row
	 *
	 * @param 	String 	$title 	Name of the Category
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getCatLine($title) {
		$ret = Array();
		
		$ret[] = "<tr>";
		$ret[] = "\t<td class=\"tdcat\" colspan=\"".$this->cols."\">".$title."</td>";
		$ret[] = "</tr>";
		
		return $ret;
	}
	
	/**
	 * Gets an file selection row
	 *
	 * @param 	String 	$label 	Label of the Row
	 * @param 	String 	$name 	Name of the Field
	 * @param 	String 	$value 	Value of the Field
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getFileLine($label,$name,$value) {
		$ret = Array();
		
		if($must != FALSE) {
			$must = 'style="color:red;"';	
		} else {
			$must = '';
		}
		
		$ret[] = "<tr>";
		$ret[] = "\t<td class=\"tdlabel\" ".$must.">".$label."</td><td class=\"tdfield\"><input type=\"file\" name=\"".$name."\" value=\"".$value."\" /></td>";
		$ret[] = "</tr>";
		
		return $ret;
	}
	
	/**
	 * Gets an input row
	 *
	 * @param 	String 	$label	Label of the Row
	 * @param 	String 	$name 	Name of the Field
	 * @param 	String 	$value 	Value of the Field
	 * @param 	Boolean	$must 	Is this a MUST field
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getInputLine($label,$name,$value,$must=FALSE) {
		$ret = Array();
		
		if($must != FALSE) {
			$must = 'style="color:red;"';	
		} else {
			$must = '';
		}
		
		$ret[] = "<tr>";
		$ret[] = "\t<td class=\"tdlabel\" ".$must.">".$label."</td><td class=\"tdfield\">";
		$ret = array_merge($ret,$this->getInputField($name,$value));
		$ret[] = "\t</td>";
		$ret[] = "</tr>";
		
		return $ret;
	}
	
	/**
	 * Gets a select row
	 *
	 * @param 	String 	$label		Label of the Row
	 * @param 	String 	$name		Name of the Field
	 * @param 	Array	$arr 		Options of the Field
	 * @param 	String 	$selected 	The selected option
	 * @param 	Boolean	$must 	Is this a MUST field
	 * @param	String	$onChange	JavaScript function to call on change
	 * @return	Array 		Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getSelectLine($label,$name,$arr,$selected,$must,$onChange='') {
		$ret = Array();
		
		// FIXME: ?!
		if($must != FALSE) {
			$must = 'style="color:red;"';	
		} else {
			$must = '';
		}
		
		$ret[] = "<tr>";
		$ret[] = "\t<td class=\"tdlabel\" ".$must.">".$label."</td><td class=\"tdfield\">";
		$ret = array_merge($ret,$this->getSelectField($name,$arr,$onChange));
		$ret[] = "\t</td>";
		$ret[] = "</tr>";
		$ret[] = '<script>document.'.$this->name.'.'.$name.'.value=\''.$selected.'\';</script>';
		
		return $ret;
	}
	
	/**
	 * Gets a submit row
	 *
	 * @param 	String 	$label 	Label of the button
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getSubmitLine($label) {
		$ret = Array();
		
		$ret[] = "<tr>";
		$ret[] = "\t<td class=\"tdlabel\" colspan=\"".$this->cols."\" align=\"center\">";
		$ret = array_merge($ret,$this->getSubmitField($label));
		$ret[] = "\t</td>";
		$ret[] = "</tr>";
		
		return $ret;
	}
	
	/**
	 * Initializes the HTML-Form
	 *
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function initForm() {
		$ret = Array();
		
		$ret[] = "<table name=\"mytable\">";
		$ret[] = "\t<form name=\"".$this->name."\" id=\"".$this->id."\" method=\"".$this->method."\" action=\"".$this->action."\" enctype=\"".$this->enctype."\" onsubmit=\"".$this->onSubmit."\">";
		
		return $ret;
	}
	
	/**
	 * Closes the HTML-Form
	 *
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function closeForm() {
		$ret = Array();
		
		$ret[] = "\t</form>";
		$ret[] = "</table>";
		
		return $ret;
	}
	
	/**
	 * Gets a submit field
	 *
	 * @param 	String 	$label	Label of the button
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getSubmitField($label) {
		$ret = Array();
		$ret[] = "\t\t<input class=\"submit\" type=\"submit\" name=\"submit\" id=\"commit\" value=\"".$label."\" />";
		
		return $ret;
	}
	
	/**
	 * Gets a select field
	 *
	 * @param 	String 	$name 		Name of the Field
	 * @param 	Array	$arr 		Options of the Field
	 * @param	String	$onChange	JavaScript function to call on change
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getSelectField($name,$arr,$onChange='') {
		$ret = Array();
		$ret[] = "\t\t<select name=\"".$name."\" onChange=\"".$onChange."\">";
		$ret[] = "\t\t\t<option value=\"\"></option>";
		
		foreach($arr AS $val) {
			if(is_array($val)) {
				$ret[] = "\t\t\t<option value=\"".$val['value']."\">".$val['label']."</option>";
			} else {
				$ret[] = "\t\t\t<option value=\"".$val."\">".$val."</option>";
			}
		}
		$ret[] = "\t\t</select>";
		
		return $ret;
	}
	
	/**
	 * Gets an input field
	 *
	 * @param 	String 	$name 	Name of the Field
	 * @param 	Array	$value 	Value of the Field
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getInputField($name,$value) {
		$ret = Array();
		$ret[] = "\t\t<input type=\"text\" name=\"".$name."\" value=\"".$value."\" />";
		
		return $ret;
	}
}