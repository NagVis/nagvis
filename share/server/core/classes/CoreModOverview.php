<?php
/*******************************************************************************
 *
 * CoreModOverview.php - Core Overview module to handle ajax requests
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModOverview extends CoreModule {
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array(
			'getOverviewProperties' => REQUIRES_AUTHORISATION,
			'getOverviewMaps' => REQUIRES_AUTHORISATION,
			'getOverviewAutomaps' => REQUIRES_AUTHORISATION,
			'getOverviewRotations' => REQUIRES_AUTHORISATION
		);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'getOverviewProperties':
					$sReturn = $this->getOverviewProperties();
				break;
				case 'getOverviewMaps':
					$sReturn = $this->getOverviewMaps();
				break;
				case 'getOverviewAutomaps':
					$sReturn = $this->getOverviewAutomaps();
				break;
				case 'getOverviewRotations':
					$sReturn = $this->getOverviewRotations();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function getOverviewProperties() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$OVERVIEW = new GlobalIndexPage($this->CORE, $BACKEND);
		return $OVERVIEW->parseIndexPropertiesJson();
	}
	
	private function getOverviewMaps() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$OVERVIEW = new GlobalIndexPage($this->CORE, $BACKEND);
		return $OVERVIEW->parseMapsJson();
	}
	
	private function getOverviewAutomaps() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$OVERVIEW = new GlobalIndexPage($this->CORE, $BACKEND);
		return $OVERVIEW->parseAutomapsJson();
	}
	
	private function getOverviewRotations() {
		// Initialize backends
		$BACKEND = new GlobalBackendMgmt($this->CORE);
		
		$OVERVIEW = new GlobalIndexPage($this->CORE, $BACKEND);
		return $OVERVIEW->parseRotationsJson();
	}
}
?>
