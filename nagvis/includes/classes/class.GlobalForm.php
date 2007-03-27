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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::GlobalForm(Array(...))');
		$this->name = $prop['name'];
		$this->id = $prop['id'];
		$this->method = (isset($prop['method'])) ? $prop['method']:'';
		$this->action = (isset($prop['action'])) ? $prop['action']:'';
		$this->onSubmit = (isset($prop['onSubmit'])) ? $prop['onSubmit']:'';
		$this->cols = (isset($prop['cols'])) ? $prop['cols']:'';
		$this->enctype = (isset($prop['enctype'])) ? $prop['enctype']:'';
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::GlobalForm()');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getHiddenField('.$name.','.$value.')');
		$ret = Array();
		
		$ret[] = '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getHiddenField(): Array(...)');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getTitleLine('.$title.')');
		$ret = Array();
		
		$ret[] = '<tr><td class="tdtitle" colspan="'.$this->cols.'">'.$title.'</td></tr>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getTitleLine(): Array(...)');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getCatLine('.$title.')');
		$ret = Array();
		
		$ret[] = '<tr><td class="tdcat" colspan="'.$this->cols.'">'.$title.'</td></tr>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getCatLine(): Array(...)');
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
	function getFileLine($label,$name,$value,$must=FALSE) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getFileLine('.$label.','.$name.','.$value.','.$must.')');
		$ret = Array();
		
		if($must != FALSE) {
			$must = 'style="color:red;"';	
		} else {
			$must = '';
		}
		
		$ret[] = '<tr><td class="tdlabel" '.$must.'>'.$label.'</td><td class="tdfield"><input type="file" name="'.$name.'" value="'.$value.'" /></td></tr>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getFileLine(): Array(...)');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getInputLine('.$label.','.$name.','.$value.','.$must.')');
		$ret = Array();
		
		if($must != FALSE) {
			$must = 'style="color:red;"';	
		} else {
			$must = '';
		}
		
		$ret[] = '<tr><td class="tdlabel" '.$must.'>'.$label.'</td><td class="tdfield">';
		$ret = array_merge($ret,$this->getInputField($name,$value));
		$ret[] = '</td></tr>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getInputLine(): Array(...)');
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
	function getSelectLine($label,$name,$arr,$selected,$must=FALSE,$onChange='') {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getSelectLine('.$label.','.$name.',Array(...),'.$selected.','.$must.','.$onChange.')');
		$ret = Array();
		
		if($must) {
			$color = ' style="color:red;"';	
		} else {
			$color = '';
		}
		
		$ret[] = '<tr><td class="tdlabel"'.$color.'>'.$label.'</td><td class="tdfield">';
		$ret = array_merge($ret,$this->getSelectField($name,$arr,$onChange,$must));
		$ret[] = '/td></tr><script>document.'.$this->name.'.'.$name.'.value="'.$selected.'";</script>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getSelectLine(): Array(...)');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getSubmitLine('.$label.')');
		$ret = Array();
		
		$ret[] = '<tr><td class="tdlabel" colspan="'.$this->cols.'" align="center">';
		$ret = array_merge($ret,$this->getSubmitField($label));
		$ret[] = '</td></tr>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getSubmitLine(): Array(...)');
		return $ret;
	}
	
	/**
	 * Initializes the HTML-Form
	 *
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function initForm() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::initForm()');
		$ret = Array();
		
		$ret[] = '<form name="'.$this->name.'" id="'.$this->id.'" method="'.$this->method.'" action="'.$this->action.'" enctype="'.$this->enctype.'" onsubmit="'.$this->onSubmit.'">';
		$ret[] = '<table name="mytable" id="table_'.$this->id.'">';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::initForm(): Array(...)');
		return $ret;
	}
	
	/**
	 * Closes the HTML-Form
	 *
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function closeForm() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::closeForm()');
		$ret = Array('</table></form>');
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::closeForm(): Array(...)');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getSubmitField('.$label.')');
		$ret = Array();
		$ret[] = '<input class="submit" type="submit" name="submit" id="commit" value="'.$label.'" />';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getSubmitField(): Array(...)');
		return $ret;
	}
	
	/**
	 * Gets a select field
	 *
	 * @param 	String 	$name 		Name of the Field
	 * @param 	Array	$arr 		Options of the Field
	 * @param	String	$onChange	JavaScript function to call on change
	 * @param	Boolean $must 		This option has to be set
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getSelectField($name,$arr,$onChange='',$must=FALSE) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getSelectField('.$name.',Array(...),'.$onChange.','.$must.')');
		$ret = Array();
		$ret[] = '<select name="'.$name.'" onChange="'.$onChange.'">';
		
		if(!$must) {
			$ret[] = '<option value=""></option>';
		}
		
		foreach($arr AS $val) {
			if(is_array($val)) {
				$ret[] = '<option value="'.$val['value'].'">'.$val['label'].'</option>';
			} else {
				$ret[] = '<option value="'.$val.'">'.$val.'</option>';
			}
		}
		$ret[] = '</select>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getSelectField(): Array(...)');
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
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalForm::getInputField('.$name.','.$value.')');
		$ret = Array();
		$ret[] = '<input type="text" name="'.$name.'" value="'.$value.'" />';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalForm::getInputField(): Array(...)');
		return $ret;
	}
}