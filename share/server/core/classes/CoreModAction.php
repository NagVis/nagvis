<?php
/*******************************************************************************
 *
 * CoreModAction.php - Core module to handle object actions
 *
 * Copyright (c) 2004-2012 NagVis Project (Contact: info@nagvis.org)
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

    public function __construct(GlobalCore $CORE) {
        // Register valid actions
        $this->aActions = Array(
            'acknowledge'       => 'perform',
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'acknowledge':
                    $aOpts = Array(
                        'map'       => MATCH_MAP_NAME,
                        'object_id' => MATCH_OBJECTID,
                        'comment'   => MATCH_STRING_EMPTY,
                        'sticky'    => MATCH_BOOLEAN_EMPTY,
                        'notify'    => MATCH_BOOLEAN_EMPTY,
                        'persist'   => MATCH_BOOLEAN_EMPTY,
                    );
                    $aVals = $this->getCustomOptions($aOpts, Array(), true);

                    $VIEW = new NagVisViewAck();

                    $err     = null;
                    $success = null;
                    if($this->submitted()) {
                        try {
                            $success = $this->handleAck($aVals);
                        } catch(FieldInputError $e) {
                            $err = $e;
                        }
                    }

                    $sReturn = json_encode(Array('code' => $VIEW->parse($aVals, $err, $success)));
                break;
            }
        }

        return $sReturn;
    }

    private function submitted() {
        return isset($_REQUEST['submit']) && $_REQUEST['submit'] != '';
    }

    protected function handleAck($attrs) {
        $this->verifyMapExists($attrs['map']);
        $MAPCFG = new GlobalMapCfg(GlobalCore::getInstance(), $attrs['map']);

        try {
            $MAPCFG->skipSourceErrors();
            $MAPCFG->readMapConfig();
        } catch(MapCfgInvalid $e) {}

        if(!isset($attrs['object_id']) && $attrs['object_id'] == '')
            throw new NagVisException(l('The object_id value is missing.'));

        $objId = $attrs['object_id'];

        if(!$MAPCFG->objExists($objId))
            throw new NagVisException(l('The object does not exist.'));

        $type  = $MAPCFG->getValue($objId, 'type');

        if($type == 'host')
            $spec = $MAPCFG->getValue($objId, 'host_name');
        else
            $spec = $MAPCFG->getValue($objId, 'host_name').';'.$MAPCFG->getValue($objId, 'service_description');

        if(!isset($attrs['comment']) || $attrs['comment'] == '')
            throw new FieldInputError('comment', l('The attribute needs to be set.'));

        if(!isset($attrs['sticky']) || !isset($attrs['notify']) || !isset($attrs['persist']))
            throw new NagVisException(l('Needed value missing.'));

        // Now send the acknowledgement
        global $_BACKEND, $AUTH;
        $BACKEND = $_BACKEND->getBackend($MAPCFG->getValue($attrs['object_id'], 'backend_id'));
        $BACKEND->actionAcknowledge(
            $type, $spec, $attrs['comment'],
            $attrs['sticky'] == '1',
            $attrs['notify'] == '1',
            $attrs['persist'] == '1',
            $AUTH->getUser()
        );

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
