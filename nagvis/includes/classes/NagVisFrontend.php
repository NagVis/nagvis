<?php
/*****************************************************************************
 *
 * NagVisFrontend.php - Class for handling the NagVis frontend
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
class NagVisFrontend extends GlobalPage {
	var $CORE;
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	var $LANG;
	
	var $ROTATION;
	
	var $MAP;
	
	var $headerTemplate;
	var $htmlBase;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisFrontend(&$CORE,&$MAPCFG = '',&$BACKEND = '',&$ROTATION= '') {
		$prop = Array();
		
		$this->CORE = &$CORE;
		$this->MAINCFG = &$CORE->MAINCFG;
		$this->LANG = &$CORE->LANG;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		
		if(!$ROTATION) {
			$this->ROTATION = new NagVisRotation($this->CORE);
		} else {
			$this->ROTATION = &$ROTATION;
		}
		
		$this->htmlBase = $this->MAINCFG->getValue('paths','htmlbase');
		
		$prop['title'] = $this->MAINCFG->getValue('internal', 'title');
		$prop['cssIncludes'] = Array($this->htmlBase.'/nagvis/includes/css/style.css');
		$prop['jsIncludes'] = Array($this->htmlBase.'/nagvis/includes/js/nagvis.js', $this->htmlBase.'/nagvis/includes/js/overlib.js', $this->htmlBase.'/nagvis/includes/js/dynfavicon.js', $this->htmlBase.'/nagvis/includes/js/ajax.js', $this->htmlBase.'/nagvis/includes/js/hover.js', $this->htmlBase.'/nagvis/includes/js/wz_jsgraphics.js', $this->htmlBase.'/nagvis/includes/js/lines.js');
		$prop['extHeader'] = '<link rel="shortcut icon" href="'.$this->htmlBase.'/nagvis/images/internal/favicon.png">';
		$prop['languageRoot'] = 'nagvis';
		
		// Only do this, when a map needs to be displayed
		if(get_class($this->MAPCFG) != '') {
			$this->headerTemplate = $this->MAPCFG->getValue('global', 0, 'header_template');
			
			$prop['extHeader'] .= '<style type="text/css">body.main { background-color: '.$this->MAPCFG->getValue('global',0, 'background_color').'; }</style>';
			$prop['allowedUsers'] = $this->MAPCFG->getValue('global',0, 'allowed_user');
		} else {
			$this->headerTemplate = $this->MAINCFG->getValue('defaults', 'headertemplate');
		}
		
		parent::GlobalPage($CORE, $prop);
	}
	
	/**
	 * If enabled, the header menu is added to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHeaderMenu() {
		if($this->MAINCFG->getValue('global', 'displayheader') == '1') {
			if($this->checkHeaderTemplateReadable(1)) {
				$ret = file_get_contents($this->MAINCFG->getValue('paths','headertemplate').'tmpl.'.$this->headerTemplate.'.html');
				
				// Replace some macros
				if(get_class($this->MAPCFG) != '') {
					$ret = str_replace('[current_map]',$this->MAPCFG->getName(),$ret);
					$ret = str_replace('[current_map_alias]',$this->MAPCFG->getValue('global', '0', 'alias'),$ret);
				}
				$ret = str_replace('[html_base]',$this->htmlBase,$ret);
				$ret = str_replace('[html_templates]',$this->MAINCFG->getValue('paths','htmlheadertemplates'),$ret);
				$ret = str_replace('[html_template_images]',$this->MAINCFG->getValue('paths','htmlheadertemplateimages'),$ret);
				// Replace language macros
				$ret = str_replace('[lang_select_map]',$this->LANG->getText('selectMap'),$ret);
				$ret = str_replace('[lang_edit_map]',$this->LANG->getText('editMap'),$ret);
				$ret = str_replace('[lang_need_help]',$this->LANG->getText('needHelp'),$ret);
				$ret = str_replace('[lang_online_doc]',$this->LANG->getText('onlineDoc'),$ret);
				$ret = str_replace('[lang_forum]',$this->LANG->getText('forum'),$ret);
				$ret = str_replace('[lang_support_info]',$this->LANG->getText('supportInfo'),$ret);
				$ret = str_replace('[lang_overview]',$this->LANG->getText('overview'),$ret);
				$ret = str_replace('[lang_instance]',$this->LANG->getText('instance'),$ret);
				$ret = str_replace('[lang_rotation_start]',$this->LANG->getText('rotationStart'),$ret);
				$ret = str_replace('[lang_rotation_stop]',$this->LANG->getText('rotationStop'),$ret);
				$ret = str_replace('[lang_refresh_start]',$this->LANG->getText('refreshStart'),$ret);
				$ret = str_replace('[lang_refresh_stop]',$this->LANG->getText('refreshStop'),$ret);
				// Replace lists
				if(preg_match_all('/<!-- BEGIN (\w+) -->/',$ret,$matchReturn) > 0) {
					foreach($matchReturn[1] AS &$key) {
						if($key == 'maplist') {
							$sReplace = '';
							preg_match_all('/<!-- BEGIN '.$key.' -->((?s).*)<!-- END '.$key.' -->/',$ret,$matchReturn1);
							foreach($this->CORE->getAvailableMaps() AS $mapName) {
								$MAPCFG1 = new NagVisMapCfg($this->CORE, $mapName);
								$MAPCFG1->readMapConfig(1);
								
								if($MAPCFG1->getValue('global',0, 'show_in_lists') == 1 && ($mapName != '__automap' || ($mapName == '__automap' && $this->MAINCFG->getValue('automap', 'showinlists')))) {
									$sReplaceObj = str_replace('[map_name]',$MAPCFG1->getName(),$matchReturn1[1][0]);
									$sReplaceObj = str_replace('[map_alias]',$MAPCFG1->getValue('global', '0', 'alias'),$sReplaceObj);
									
									// Add defaultparams to map selection
									if($mapName == '__automap') {
										$sReplaceObj = str_replace('[url_params]', $this->MAINCFG->getValue('automap', 'defaultparams'), $sReplaceObj);
									} else {
										$sReplaceObj = str_replace('[url_params]','',$sReplaceObj);
									}
									
									// auto select current map
									if(get_class($this->MAPCFG) != '' && $mapName == $this->MAPCFG->getName() || ($mapName == '__automap' && isset($_GET['automap']))) {
										$sReplaceObj = str_replace('[selected]','selected="selected"',$sReplaceObj);
									} else {
										$sReplaceObj = str_replace('[selected]','',$sReplaceObj);
									}
									
									$sReplace .= $sReplaceObj;
								}
							}
							$ret = preg_replace('/<!-- BEGIN '.$key.' -->(?:(?s).*)<!-- END '.$key.' -->/',$sReplace,$ret);
						}
					}
				}
				
				$this->addBodyLines('<div class="header">'.$ret.'</div>');
			}
		}
	}
	
	/**
	 * Checks for existing header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHeaderTemplateExists($printErr) {
		if(file_exists($this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->headerTemplate.'.html')) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->CORE);
				$FRONTEND->messageToUser('WARNING', $this->LANG->getText('headerTemplateNotExists','FILE~'.$this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->headerTemplate.'.html'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Checks for readable header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHeaderTemplateReadable($printErr) {
		if($this->checkHeaderTemplateExists($printErr) && is_readable($this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->headerTemplate.'.html')) {
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->CORE);
				$FRONTEND->messageToUser('WARNING', $this->LANG->getText('headerTemplateNotReadable','FILE~'.$this->MAINCFG->getValue('paths', 'headertemplate').'tmpl.'.$this->headerTemplate.'.html'));
			}
			return FALSE;
		}
	}
	
	/**
	 * Adds the index to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIndexPage() {
		$this->addBodyLines('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');
		$this->addBodyLines('<div class="infopage">');
		$this->INDEX = new GlobalIndexPage($this->CORE, $this->BACKEND);
		$this->addBodyLines($this->INDEX->parse());
		$this->addBodyLines('</div>');
	}
	
	/**
	 * Adds the map to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMap() {
		$this->addBodyLines('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');
		$this->addBodyLines('<div class="map">');
		$this->MAP = new NagVisMap($this->CORE, $this->MAPCFG, $this->BACKEND);
		$this->MAP->MAPOBJ->checkMaintenance(1);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines('</div>');
	}
	
	/**
	 * Adds the automap to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getAutoMap($arrOptions) {
		$this->addBodyLines('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');
		$this->addBodyLines('<div id="map" class="map">');
		$this->MAP = new NagVisAutoMap($this->CORE, $this->BACKEND, $arrOptions);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines('</div>');
	}
	
	/**
	 * Adds the user messages to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMessages() {
		$this->addBodyLines($this->getUserMessages());
	}

	/**
	 * Gets the javascript code for the map refresh/rotation
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getRefresh() {
		$strReturn = "";
		if(isset($_GET['rotation']) && $_GET['rotation'] != '' && (!isset($_GET['rotate']) || (isset($_GET['rotate']) && $_GET['rotate'] == '1'))) {
			$strReturn .= "var rotate = true;\n";
		} else {
			$strReturn .= "var rotate = false;\n";
		}
		
		$strReturn .= "var bRefresh = true;\n";
		$strReturn .= "var nextRotationUrl = '".$this->ROTATION->getNextStepUrl()."';\n";
		$strReturn .= "var nextRefreshTime = '".$this->ROTATION->getStepInterval()."';\n";
		$strReturn .= "var oRotation = window.setTimeout('countdown()', 1000);\n";
		
		return $this->parseJs($strReturn);
	}
}
?>
