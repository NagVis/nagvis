<?php
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
