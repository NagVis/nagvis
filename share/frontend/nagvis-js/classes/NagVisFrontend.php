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
	private $MAPCFG;
	private $BACKEND;
	
	private $ROTATION;
	
	private $MAP;
	
	private $headerTemplate;
	private $htmlBase;
	private $htmlCss;
	private $htmlJs;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, $MAPCFG = '', $BACKEND = '', $ROTATION= '') {
		$prop = Array();
		
		$this->MAPCFG = $MAPCFG;
		$this->BACKEND = $BACKEND;
		
		if(!$ROTATION) {
			$this->ROTATION = new NagVisRotation($CORE);
		} else {
			$this->ROTATION = $ROTATION;
		}
		
		$this->htmlBase = $CORE->MAINCFG->getValue('paths','htmlbase');
		$this->htmlCss = $CORE->MAINCFG->getValue('paths','htmlcss');
		$this->htmlJs = $CORE->MAINCFG->getValue('paths','htmljs');
		
		$prop['title'] = $CORE->MAINCFG->getValue('internal', 'title');
		$prop['cssIncludes'] = Array($this->htmlCss.'style.css',
		                             $this->htmlCss.'frontendEventlog.css');
		$prop['jsIncludes'] = Array($this->htmlJs.'nagvis.js',
															$this->htmlJs.'ExtBase.js',
															$this->htmlJs.'frontendMessage.js',
															$this->htmlJs.'frontendEventlog.js',
															$this->htmlJs.'hover.js',
															$this->htmlJs.'frontendContext.js',
															$this->htmlJs.'ajax.js',
															$this->htmlJs.'dynfavicon.js',
															$this->htmlJs.'frontend.js',  
															$this->htmlJs.'lines.js',
															$this->htmlJs.'overlib.js',
															$this->htmlJs.'NagVisObject.js',
															$this->htmlJs.'NagVisStatefulObject.js',
															$this->htmlJs.'NagVisStatelessObject.js',
															$this->htmlJs.'NagVisHost.js',
															$this->htmlJs.'NagVisService.js',
															$this->htmlJs.'NagVisHostgroup.js',
															$this->htmlJs.'NagVisServicegroup.js',
															$this->htmlJs.'NagVisMap.js',
															$this->htmlJs.'NagVisShape.js',
															$this->htmlJs.'NagVisTextbox.js',
															$this->htmlJs.'NagVisRotation.js',
															$this->htmlJs.'wz_jsgraphics.js');
		$prop['extHeader'] = '<link rel="shortcut icon" href="'.$this->htmlBase.'/nagvis/images/internal/favicon.png">';
		$prop['languageRoot'] = 'nagvis';
		
		// Only do this, when a map needs to be displayed
		if($this->MAPCFG !== null && get_class($this->MAPCFG) != '') {
			$prop['allowedUsers'] = $this->MAPCFG->getValue('global',0, 'allowed_user');
			
			// Load custom (map specific) stylesheets
			$customStylesheet = $this->MAPCFG->getValue('global',0, 'stylesheet');
			if($customStylesheet !== '') {
				$prop['cssIncludes'][] = $CORE->MAINCFG->getValue('paths','htmlstyles') . $customStylesheet;
			}
		}
		
		parent::__construct($CORE, $prop);
	}
	
	/**
	 * If enabled, the header menu is added to the page
	 *
	 * @param  Bool    Enable/Disable the header menu
	 * @param  String  Header template name
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getHeaderMenu($headerMenuEnabled, $headerTemplateName) {
		if($headerMenuEnabled) {
			// Parse the header menu
			$HEADER = new GlobalHeaderMenu($this->CORE, $headerTemplateName, $this->MAPCFG);
			$this->addBodyLines($HEADER->__toString());
		}
	}
	
	/**
	 * Adds the index to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getIndexPage() {
		$this->addBodyLines('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');
		$this->addBodyLines('<div id="overview" class="infopage"></div>');
		$this->INDEX = new GlobalIndexPage($this->CORE, $this->BACKEND);
		$this->addBodyLines($this->parseJs($this->INDEX->parseJson()));
	}
	
	/**
	 * Adds the map to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getMap() {
		$this->addBodyLines('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');
		$this->addBodyLines('<div id="map" class="map"></div>');
		//FIXME: Need recoding here: $this->MAPCFG is the mapname and $this->MAP should be $this->VIEW
		$this->MAP = new NagVisMapView($this->CORE, $this->MAPCFG);
		//FIXME: $this->MAP->MAPOBJ->checkMaintenance(1);
		$this->addBodyLines($this->parseJs($this->MAP->parse()));
	}
	
	/**
	 * Adds the automap to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getAutoMap($arrOptions) {
		$this->addBodyLines('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');
		$this->addBodyLines('<div id="map" class="map">');
		$this->MAP = new NagVisAutoMap($this->CORE, $this->MAPCFG, $this->BACKEND, $arrOptions);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines('</div>');
		$this->addBodyLines($this->parseJs($this->MAP->parseMapJson()));
	}
	
	/**
	 * Gets the javascript code for the map refresh/rotation
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function getRotationPropertiesJson($bRefresh) {
		$arr = Array();
		
		if($this->ROTATION->getPoolName() != '') {
			$arr['rotationEnabled'] = 1;
			$arr['nextStepUrl'] = $this->ROTATION->getNextStepUrl();
			$arr['nextStepTime'] = $this->ROTATION->getStepInterval();
		} else {
			$arr['rotationEnabled'] = 0;
			$arr['nextStepUrl'] = '';
			if($bRefresh) {
				$arr['nextStepTime'] = $this->ROTATION->getStepInterval();
			} else {
				$arr['nextStepTime'] = '';
			}
		}
		
		return json_encode($arr);
	}
}
?>
