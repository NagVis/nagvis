<?php
/*****************************************************************************
 *
 * NagVisTextbox.php - Class of a text object in NagVis with all necessary 
 *                  informations which belong to the object handling in NagVis
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
class NagVisTextbox extends NagVisStatelessObject {
	var $CORE;
	
	var $background_color;
	var $border_color;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisTextbox(&$CORE) {
		$this->CORE = &$CORE;
		
		$this->class = 'box';
		$this->background_color = '#CCCCCC';
		
		$this->type = 'textbox';
		parent::NagVisStatelessObject($this->CORE);
	}
	
	/**
	 * PUBLIC parse()
	 *
	 * Parses the object
	 *
	 * @return	String		HTML code of the object
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parse() {
		$this->replaceMacros();
		return $this->parseTextbox();
	}
	
	# End public methods
	# #########################################################################
	
	/**
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceMacros() {
		
		$this->text = str_replace('[refresh_counter]','<font id="refreshCounter"></font>', $this->text);
		
	}
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @return	String	String with HTML Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseTextbox() {
		$ret = '<div class="'.$this->class.'" style="border-color:'.$this->border_color.';background:'.$this->background_color.';left:'.$this->x.'px;top:'.$this->y.'px;width:'.$this->w.'px;overflow:visible;">';	
		$ret .= "\t".'<span>'.$this->text.'</span>';
		$ret .= '</div>';
		return $ret;	
	}
	
	/**
	 * Just a dummy here (Textbox won't need an icon)
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function fetchIcon() {
		// Nothing to do here, icon is set in constructor
	}
}
?>
