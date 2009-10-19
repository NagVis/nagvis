<?php
class FrontendModRotation extends FrontendModule {
	private $name = '';
	private $type = '';
	private $step = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;

		// Parse the view specific options
		$aOpts = Array('show' => MATCH_ROTATION_NAME,
		               'type' => MATCH_ROTATION_STEP_TYPES_EMPTY,
		               'step' => MATCH_STRING_NO_SPACE_EMPTY);
		
		$aVals = $this->getCustomOptions($aOpts);
		$this->name = $aVals['show'];
		$this->type = $aVals['type'];
		$this->step = $aVals['step'];
		
		// Register valid actions
		$this->aActions = Array(
			'view' => REQUIRES_AUTHORISATION
		);
		
		// Register valid objects
		$this->aObjects = $this->CORE->getDefinedRotationPools();
		
		// Set the requested object for later authorisation
		$this->setObject($this->name);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					// Show the view dialog to the user
					$sReturn = $this->showViewDialog();
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function showViewDialog() {
		// Initialize rotation/refresh
		$ROTATION = new FrontendRotation($this->CORE, $this->name);
		
		// Set the requested step
		$ROTATION->setStep($this->type, $this->step);
		
		switch($this->type) {
			case '':
				// If no step given redirect to first step
				header('Location: ' . $ROTATION->getStepUrlById(0));
			break;
			case 'map':
			case 'url':
				header('Location: ' . $ROTATION->getCurrentStepUrl());
			break;
			case 'automap':
				// FIXME: Automaps in rotations
				echo 'Error: Automaps in rotations are not implemented yet';
				exit(0);
			break;
		}
	}
}
?>
