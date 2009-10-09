<?php
class FrontendModUrl extends FrontendModule {
	private $CORE;
	private $name = '';
	private $url = '';
	
	public function __construct(GlobalCore $CORE) {
		$this->CORE = $CORE;

		$UHANDLER = new CoreUriHandler($this->CORE); 
		$this->name = $UHANDLER->get('show');

		// And parse the view specific options
		$this->getUrlOptions($UHANDLER);
		
		$this->aActions = Array(
			'view' => REQUIRES_AUTHORISATION
		);
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

	private function getUrlOptions(CoreUriHandler $UHANDLER) {
		// FIXME: The array should contain orders for validating the given value
		// the options should be validated by the uri handler
		
		// Parse view specific uri params
		$aKeys = Array('url' => '');
		
		// Load the specific params to the UriHandler
		$UHANDLER->parseModSpecificUri($aKeys);
		
		// Now get those params
		$this->url = $UHANDLER->get('url');
	}
	
	private function showViewDialog() {
		// Only show when map name given
		if(!isset($this->url) || $this->url == '') {
			//FIXME: Error handling
			echo "No url given";
			exit(1);
		}
		
		// Build index template
		$INDEX = new NagVisIndexView($this->CORE);
		
		// Need to parse the header menu?
		if($this->CORE->MAINCFG->getValue('index','headermenu')) {
      // Parse the header menu
      $HEADER = new GlobalHeaderMenu($this->CORE, $this->CORE->MAINCFG->getValue('index', 'headertemplate'));
			$INDEX->setHeaderMenu($HEADER->__toString());
    }
		
		// Initialize map view
		$this->VIEW = new NagVisUrlView($this->CORE, $this->url);
    $INDEX->setContent($this->VIEW->parse());

		return $INDEX->parse();

		//FIXME: Rotation properties not supported atm
    //$FRONTEND->addBodyLines($FRONTEND->parseJs('oRotationProperties = '.$FRONTEND->getRotationPropertiesJson(0).';'));
	}
}
?>
