<?php
/*******************************************************************************
 *
 * CoreModAction.php - Core module to handle object actions
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
class CoreModAction extends CoreModule {
    private $name = null;
    private $MAPCFG = null;

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'Action';

        // Register valid actions
        $this->aActions = Array(
            'acknowledge'       => 'perform',
            'custom_action'     => 'perform',
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'custom_action':
                    $aOpts = Array(
                        'map'       => MATCH_MAP_NAME,
                        'object_id' => MATCH_OBJECTID,
                        'cmd'       => MATCH_STRING_NO_SPACE,
                    );
                    $attrs = $this->getCustomOptions($aOpts, Array());

                    // Input validations
                    // - Valid custom action?
                    $actions = GlobalCore::getInstance()->getDefinedCustomActions();
                    if(!isset($actions[$attrs['cmd']]))
                        throw new NagVisException(l('The given custom action is not defined.'));

                    // - does the map exist?
                    $this->verifyMapExists($attrs['map']);

                    // - does the object exist on the map?
                    $this->MAPCFG = new GlobalMapCfg($attrs['map']);
                    $this->MAPCFG->skipSourceErrors();
                    $this->MAPCFG->readMapConfig();

                    if(!isset($attrs['object_id']) && $attrs['object_id'] == '')
                        throw new NagVisException(l('The object_id value is missing.'));
                    
                    if(!$this->MAPCFG->objExists($attrs['object_id']))
                        throw new NagVisException(l('The object does not exist.'));
                    $objId = $attrs['object_id'];

                    $func = 'handle_action_'.$attrs['cmd'];
                    if(!function_exists($func))
                        throw new NagVisException(l('Action handler not implemented.'));

                    $func($this->MAPCFG, $objId);

                break;
                
                case 'acknowledge':
                    $aOpts = Array(
                        'map'       => MATCH_MAP_NAME,
                        'object_id' => MATCH_OBJECTID,
                        'comment'   => MATCH_STRING_EMPTY,
                        'sticky'    => MATCH_BOOLEAN_EMPTY,
                        'notify'    => MATCH_BOOLEAN_EMPTY,
                        'persist'   => MATCH_BOOLEAN_EMPTY,
                    );
                    $attrs = $this->getCustomOptions($aOpts, Array(), true);

                    $this->verifyMapExists($attrs['map']);

                    $this->MAPCFG = new GlobalMapCfg($attrs['map']);
                    $this->MAPCFG->skipSourceErrors();
                    $this->MAPCFG->readMapConfig();

                    $VIEW = new NagVisViewAck($this->MAPCFG);

                    $err     = null;
                    $success = null;
                    if($this->submitted()) {
                        try {
                            $success = $this->handleAck($attrs);
                        } catch(FieldInputError $e) {
                            $err = $e;
                        }
                    }

                    $sReturn = json_encode(Array('code' => $VIEW->parse($attrs, $err, $success)));
                break;
            }
        }

        return $sReturn;
    }

    private function submitted() {
        return isset($_REQUEST['submit']) && $_REQUEST['submit'] != '';
    }

    protected function handleAck($attrs) {
        if(!isset($attrs['object_id']) && $attrs['object_id'] == '')
            throw new NagVisException(l('The object_id value is missing.'));

        $objId = $attrs['object_id'];

        if(!$this->MAPCFG->objExists($objId))
            throw new NagVisException(l('The object does not exist.'));

        $type  = $this->MAPCFG->getValue($objId, 'type');

        if($type == 'host')
            $spec = $this->MAPCFG->getValue($objId, 'host_name');
        else
            $spec = $this->MAPCFG->getValue($objId, 'host_name').';'.$this->MAPCFG->getValue($objId, 'service_description');

        if(!isset($attrs['comment']) || $attrs['comment'] == '')
            throw new FieldInputError('comment', l('The attribute needs to be set.'));

        if(!isset($attrs['sticky']) || !isset($attrs['notify']) || !isset($attrs['persist']))
            throw new NagVisException(l('Needed value missing.'));

        // Now send the acknowledgement
        global $_BACKEND, $AUTH;
        $backendIds = $this->MAPCFG->getValue($attrs['object_id'], 'backend_id');
        foreach ($backendIds AS $backendId) {
            $BACKEND = $_BACKEND->getBackend($backendId);
            $BACKEND->actionAcknowledge(
                $type, $spec, $attrs['comment'],
                $attrs['sticky'] == '1',
                $attrs['notify'] == '1',
                $attrs['persist'] == '1',
                $AUTH->getUser()
            );
        }

        return l('The command has been sent to the monitoring core. Refreshing in 2 seconds');
    }

    // Check if the map exists
    private function verifyMapExists($map, $negate = false) {
        if(!$negate) {
            if(count(GlobalCore::getInstance()->getAvailableMaps('/^'.$map.'$/')) <= 0) {
                throw new NagVisException(l('The map does not exist.'));
            }
        } else {
            if(count(GlobalCore::getInstance()->getAvailableMaps('/^'.$map.'$/')) > 0) {
                throw new NagVisException(l('The map does already exist.'));
            }
        }
    }
}
?>
