<?php
/*****************************************************************************
 *
 * GlobalForm.php - Class for managing the common form. Should be used by ALL
 *                  pages of NagVis and WUI where HTML-forms are used
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalForm {
	private $name;
	private $id;
	private $method;
	private $action;
	private $onSubmit;
	private $cols;
	private $enctype;
	
	/**
	 * Class Constructor
	 *
	 * @param 	Array	$prop	Array('name'=>'myform','id'=>'myform','method'=>'POST','action'=>'','onSubmit'=>'','cols'=>'2','enctype'=>''
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($prop=Array('name'=>'myform','id'=>'myform','method'=>'POST','action'=>'','onSubmit'=>'','cols'=>'2','enctype'=>'')) {
		$this->name = $prop['name'];
		$this->id = $prop['id'];
		$this->method = (isset($prop['method'])) ? $prop['method']:'';
		$this->action = (isset($prop['action'])) ? $prop['action']:'';
		$this->onSubmit = (isset($prop['onSubmit'])) ? $prop['onSubmit']:'';
		$this->cols = (isset($prop['cols'])) ? $prop['cols']:'';
		$this->enctype = (isset($prop['enctype'])) ? $prop['enctype']:'';
	}
	
	/**
	 * Gets a hidden field
	 *
	 * @param 	String 	$name 	Name of the Field
	 * @param 	String 	$value 	Value of the Field
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHiddenField($name,$value) {
		return '<input type="hidden" id="'.$name.'" name="'.$name.'" value="'.$value.'" />';
	}
	
	/**
	 * Gets a title row
	 *
	 * @param 	String 	$title 	Name of the title
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getTitleLine($title) {
		return '<tr><th colspan="'.$this->cols.'">'.$title.'</th></tr>';
	}
	
	/**
	 * Gets a category row
	 *
	 * @param 	String 	$title 	Name of the Category
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getCatLine($title) {
		return '<tr><th class="cat" colspan="'.$this->cols.'">'.$title.'</th></tr>';
	}
	
	/**
	 * Gets a file selection row
	 *
	 * @param 	String 	$label 	Label of the Row
	 * @param 	String 	$name 	Name of the Field
	 * @param 	String 	$value 	Value of the Field
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getFileLine($label,$name,$value,$must=FALSE) {
		if($must != FALSE) {
			$must = 'style="color:red;"';	
		} else {
			$must = '';
		}
		
		return '<tr><td class="tdlabel" '.$must.'>'.$label.'</td><td class="tdfield"><input type="file" name="'.$name.'" value="'.$value.'" /></td></tr>';
	}
	
	/**
	 * Gets an input row
	 *
	 * @param 	String 	$label	Label of the Row
	 * @param 	String 	$name 	Name of the Field
	 * @param 	String 	$value 	Value of the Field
	 * @param 	Boolean	$must 	Is this a MUST field
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getInputLine($label,$name,$value=NULL,$must=FALSE,$onBlur='',$rowStyle='',$rowClass='') {
		$ret = '';
		
		if($must != FALSE) {
			$must = 'style="color:red;"';	
		} else {
			$must = '';
		}
		
		if($rowStyle != '') {
			$style = ' style="'.$rowStyle.'"';
		} else {
			$style = '';
		}
		
		if($rowClass != '') {
			$class = ' class="'.$rowClass.'"';
		} else {
			$class = '';
		}
		
		$ret .= '<tr'.$style.$class.'><td class="tdlabel" '.$must.'>'.$label.'</td><td class="tdfield">';
		$ret .= $this->getInputField($name, '', $onBlur);
		$ret .= '</td></tr>';
		
		if($value !== NULL && $value !== FALSE && $value !== '') {
			if(is_array($value)) {
				$value = implode(',',$value);
			}
			
			$ret .= '<script>document.'.$this->name.'.'.$name.'.value="'.$value.'";</script>';
		} else {
			$ret .= '<script>toggleDefaultOption(\''.$name.'\');</script>';
		}
		
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
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSelectLine($label,$name,$arr,$selected=NULL,$must=FALSE,$onChange='',$onBlur='', $rowStyle='',$rowClass='') {
		$ret = '';
		
		if($must) {
			$color = ' style="color:red;"';	
		} else {
			$color = '';
		}
		
		if($rowStyle != '') {
			$style = ' style="'.$rowStyle.'"';
		} else {
			$style = '';
		}
		
		if($rowClass != '') {
			$class = ' class="'.$rowClass.'"';
		} else {
			$class = '';
		}
		
		$ret .= '<tr'.$style.$class.'><td class="tdlabel"'.$color.'>'.$label.'</td><td class="tdfield">';
		$ret .= $this->getSelectField($name,$arr,$onChange,$must,$onBlur);
		$ret .= '</td></tr>';
		
		if($selected !== NULL && $selected !== FALSE && $selected !== '') { 
			$ret .= '<script>document.'.$this->name.'.'.$name.'.value="'.$selected.'";</script>';
		} else {
			$ret .= '<script>toggleDefaultOption(\''.$name.'\');</script>';
		}
		
		return $ret;
	}
	
	/**
	 * Gets a submit row
	 *
	 * @param 	String 	$label 	Label of the button
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSubmitLine($label) {
		$ret = '';
		
		$ret .= '<tr><td class="tdlabel" colspan="'.$this->cols.'" align="center">';
		$ret .= $this->getSubmitField($label);
		$ret .= '</td></tr>';
		
		return $ret;
	}
	
	/**
	 * Initializes the HTML-Form
	 *
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function initForm() {
		$ret = '';
		
		$method = ($this->method == '') ? '' : 'method="'.$this->method.'"';
		$onSubmit = ($this->onSubmit == '') ? '' : 'onsubmit="'.$this->onSubmit.'"';
		
		$ret .= '<form name="'.$this->name.'" id="'.$this->id.'" '.$method.' action="'.$this->action.'" enctype="'.$this->enctype.'" '.$onSubmit.'>';
		$ret .= '<table name="mytable" class="mytable" id="table_'.$this->id.'">';
		
		return $ret;
	}
	
	/**
	 * Closes the HTML-Form
	 *
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function closeForm() {
		return '</table></form>';
	}
	
	/**
	 * Gets a submit field
	 *
	 * @param 	String 	$label	Label of the button
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSubmitField($label) {
		return '<input class="submit" type="submit" name="submit" id="commit" value="'.$label.'" />';
	}
	
	/**
	 * Gets a select field
	 *
	 * @param 	String 	$name 		Name of the Field
	 * @param 	Array	$arr 		Options of the Field
	 * @param	String	$onChange	JavaScript function to call on change
	 * @param	Boolean $must 		This option has to be set
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getSelectField($name,$arr,$onChange='',$must=FALSE,$onBlur='') {
		$ret = '';
		$ret .= '<select id="'.$name.'" name="'.$name.'" onChange="'.$onChange.'" onBlur="'.$onBlur.'">';
		
		if(!$must) {
			$ret .= '<option value=""></option>';
		}
		
		foreach($arr AS &$val) {
			if(is_array($val)) {
				$ret .= '<option value="'.$val['value'].'">'.$val['label'].'</option>';
			} else {
				$ret .= '<option value="'.$val.'">'.$val.'</option>';
			}
		}
		$ret .= '</select>';
		
		return $ret;
	}
	
	/**
	 * Gets an input field
	 *
	 * @param 	String 	$name 	Name of the Field
	 * @param 	Array	$value 	Value of the Field
	 * @return	Array 	Html
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getInputField($name,$value='',$onBlur='') {
		$ret = '';
		
		$ret .= '<input id="'.$name.'" type="text" name="'.$name.'" value="'.$value.'" onBlur="'.$onBlur.'" />';
		
		return $ret;
	}
	
	/**
	 * Gets the id of the form
	 *
	 * @return	Integer  ID of the form
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getId() {
		return $this->id;
	}
}
?>