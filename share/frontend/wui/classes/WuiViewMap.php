<?php
/*****************************************************************************
 *
 * WuiViewMap.php - Class for parsing the NagVis maps in WUI
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
class WuiViewMap {
	private $CORE = null;
	private $MAP = null;
	
	private $name = '';
	
	/**
	 * Class Constructor
	 *
	 * @param    GlobalCore      $CORE
	 * @param    String          $NAME
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct(WuiCore $CORE, $MAP) {
		$this->CORE = $CORE;
		$this->MAP = $MAP;

		$this->name = $this->MAP->MAPCFG->getName();
	}
	
	/**
	 * Parses the map and the objects for the nagvis-js frontend
	 *
	 * @return	String 	String with JS Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function parse() {
		// Initialize template system
		$TMPL    = new FrontendTemplateSystem($this->CORE);
		$TMPLSYS = $TMPL->getTmplSys();
		$USERCFG = new CoreUserCfg();
		
		$aData = Array(
				'backgroundImg'     => $this->MAP->MAPCFG->BACKGROUND->getFile(),
				'base'              => $this->CORE->getMainCfg()->getValue('paths', 'htmlbase'),
				'generalProperties' => $this->CORE->getMainCfg()->parseGeneralProperties(),
				'viewProperties'    => $this->parseViewProperties(),
				'userProperties'    => $USERCFG->doGetAsJson(),
				'mapName'           => $this->name,
				'userName'          => $this->CORE->getAuthentication()->getUser(),
				'mapObjects'        => $this->MAP->parseObjects(),
				'movable'           => $this->MAP->getMoveableObjects(),
				'lang'              => $this->CORE->getJsLang(),
				'validMainCfg'      => $this->CORE->getJsValidMainConfig(),
				'validMapCfg'       => $this->MAP->getJsValidMapConfig(),
				'mapOptions'        => $this->CORE->getMapOptions(),
				'backupAvailable'   => (file_exists($this->CORE->getMainCfg()->getValue('paths', 'mapcfg').$this->MAP->MAPCFG->getName().".cfg.bak")?'true':'false')
			);

    // Build page based on the template file and the data array
    // FIXME: Make template set configurable
    return $TMPLSYS->get($TMPL->getTmplFile('default', 'wuiMap'), $aData);
	}
	
	/**
	 * Parses WUI specific settings
	 *
	 * @return  String  JSON Code
	 * @author  Lars Michelsen <lars@vertical-visions.de>
	 */
	private function parseViewProperties() {
		return json_encode(Array(
			'grid_show'  => intval($this->MAP->MAPCFG->getValue(0, 'grid_show')),
			'grid_color' =>        $this->MAP->MAPCFG->getValue(0, 'grid_color'),
			'grid_steps' => intval($this->MAP->MAPCFG->getValue(0, 'grid_steps'))
		));
	}
}
?>
