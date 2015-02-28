<?php
/*******************************************************************************
 *
 * CoreModMainCfg.php - Core Map module to handle ajax requests
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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
class CoreModMainCfg extends CoreModule {
    private $name = null;

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'MainCfg';
        $this->CORE = $CORE;

        // Register valid actions
        $this->aActions = Array(
            // WUI specific actions
            'edit'              => REQUIRES_AUTHORISATION,
            'manageBackends'    => 'edit',
            'getBackendOptions' => 'edit',
            'doBackendDefault'  => 'edit',
            'doBackendAdd'      => 'edit',
            'doBackendEdit'     => 'edit',
            'doBackendDel'      => 'edit',
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'edit':
                    $VIEW = new ViewEditMainCfg();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;

                case 'manageBackends':
                    $VIEW = new ViewManageBackends();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
                case 'getBackendOptions':
                    $sReturn = json_encode($this->handleResponse('handleResponse'.$this->sAction, $this->sAction));
                break;
                case 'doBackendDefault':
                    $this->handleResponse('handleResponseBackendDefault', $this->sAction,
                                            l('The default backend has been changed.'),
                                                                l('The default backend could not be changed.'),
                                                                1);
                break;
                case 'doBackendAdd':
                    $this->handleResponse('handleResponseBackendAdd', $this->sAction,
                                            l('The new backend has been added.'),
                                                                l('The new backend could not be added.'),
                                                                1);
                break;
                case 'doBackendEdit':
                    $this->handleResponse('handleResponseBackendEdit', $this->sAction,
                                            l('The changes have been saved.'),
                                                                l('Problem while saving the changes.'),
                                                                1);
                break;
                case 'doBackendDel':
                    $this->handleResponse('handleResponseBackendDel', $this->sAction,
                                            l('The backend has been deleted.'),
                                                                l('The backend could bot be deleted.'),
                                                                1);
                break;
            }
        }

        return $sReturn;
    }

    protected function handleResponsegetBackendOptions() {
        $FHANDLER = new CoreRequestHandler($_GET);
        $this->verifyValuesSet($FHANDLER, Array('backendid'));
        return Array('backendid' => $FHANDLER->get('backendid'));
    }

    private function getBackendAttributes($type) {
            // Loop all options for this backend type
            $aBackendOpts = $this->CORE->getMainCfg()->getValidObjectType('backend');

            // Merge global backend options with type specific options
            $aOpts = $aBackendOpts['options'][$type];
            foreach($aBackendOpts AS $sKey => $aOpt)
                if($sKey !== 'backendid' && $sKey !== 'options')
                    $aOpts[$sKey] = $aOpt;
            return $aOpts;
    }

    protected function getBackendOptions($a) {
            $aRet = Array();
            $backendType = cfg('backend_'.$a['backendid'], 'backendtype');

            foreach($this->getBackendAttributes($backendType) AS $key => $aOpt)
                if(cfg('backend_'.$a['backendid'], $key, true) !== false)
                    $aRet[$key] = cfg('backend_'.$a['backendid'], $key, true);
                else
                    $aRet[$key] = '';

            return $aRet;
    }

    protected function doBackendDefault($a) {
            $this->CORE->getUserMainCfg()->setValue('defaults', 'backend', $a['defaultbackend']);
            $this->CORE->getUserMainCfg()->writeConfig();
            return true;
    }

    protected function handleResponseBackendDefault() {
        $FHANDLER = new CoreRequestHandler($_POST);
        $this->verifyValuesSet($FHANDLER, Array('defaultbackend'));
        return Array('defaultbackend' => $FHANDLER->get('defaultbackend'));
    }

    protected function handleResponseBackendAdd() {
        $FHANDLER = new CoreRequestHandler($_POST);
        $this->verifyValuesSet($FHANDLER, Array('backendid', 'backendtype'));
        return Array('backendid'   => $FHANDLER->get('backendid'),
                     'backendtype' => $FHANDLER->get('backendtype'),
                                 'opts'        => $_POST);
    }

    protected function doBackendAdd($a) {
        $bFoundOption = false;
        $aOpt = Array();

        // Loop all aviable options for this backend
        foreach($this->getBackendAttributes($a['backendtype']) AS $key => $arr) {
            // If there is a value for this option, set it
            if(isset($a['opts'][$key]) && $a['opts'][$key] != '') {
                $bFoundOption = true;
                $aOpt[$key] = $a['opts'][$key];
            }
        }

        // If there is at least one option set...
        if($bFoundOption) {
            // Set standard values
            $this->CORE->getUserMainCfg()->setSection('backend_'.$a['backendid']);
            $this->CORE->getUserMainCfg()->setValue('backend_'.$a['backendid'], 'backendtype', $a['backendtype']);

            // Set all options
            foreach($aOpt AS $key => $val) {
                $this->CORE->getUserMainCfg()->setValue('backend_'.$a['backendid'], $key, $val);
            }
        }

        // Write the changes to the main configuration
        $this->CORE->getUserMainCfg()->writeConfig();
        return true;
    }

    protected function handleResponseBackendEdit() {
        $FHANDLER = new CoreRequestHandler($_POST);
        $this->verifyValuesSet($FHANDLER, Array('backendid'));
        return Array('backendid'   => $FHANDLER->get('backendid'),
                     'backendtype' => $this->CORE->getUserMainCfg()->getValue('backend_'.$FHANDLER->get('backendid'), 'backendtype'),
                     'opts'        => $_POST);
    }

    protected function doBackendEdit($a) {
        // Loop all aviable options for this backend and set them when some is given in the response
        foreach($this->getBackendAttributes($a['backendtype']) AS $key => $arr)
            if(isset($a['opts'][$key]))
                $this->CORE->getUserMainCfg()->setValue('backend_'.$a['opts']['backendid'], $key, $a['opts'][$key]);

        // Write the changes to the main configuration
        $this->CORE->getUserMainCfg()->writeConfig();
        return true;
    }

    protected function handleResponseBackendDel() {
        $FHANDLER = new CoreRequestHandler($_POST);
        $this->verifyValuesSet($FHANDLER, Array('backendid'));
        return Array('backendid' => $FHANDLER->get('backendid'));
    }

    protected function doBackendDel($a) {
        $this->CORE->getUserMainCfg()->delSection('backend_'.$a['backendid']);
        $this->CORE->getUserMainCfg()->writeConfig();
        return true;
    }
}
?>
