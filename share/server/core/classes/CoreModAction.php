<?php
/*******************************************************************************
 *
 * CoreModAction.php - Core module to handle object actions
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

class CoreModAction extends CoreModule {
    public function __construct(GlobalCore $CORE) {
        $this->sName = 'Action';

        // Register valid actions
        $this->aActions = Array(
            'acknowledge'       => 'perform',
            'custom_action'     => 'perform',
        );
    }

    public function handleAction() {
        global $CORE;
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
                    $actions = $CORE->getDefinedCustomActions();
                    if(!isset($actions[$attrs['cmd']]))
                        throw new NagVisException(l('The given custom action is not defined.'));

                    // - does the map exist?
                    if(count($CORE->getAvailableMaps('/^'.$attrs['map'].'$/')) <= 0) {
                        throw new NagVisException(l('The map does not exist.'));
                    }

                    // - does the object exist on the map?
                    $MAPCFG = new GlobalMapCfg($attrs['map']);
                    $MAPCFG->skipSourceErrors();
                    $MAPCFG->readMapConfig();

                    if(!isset($attrs['object_id']) && $attrs['object_id'] == '')
                        throw new NagVisException(l('The object_id value is missing.'));
                    
                    if(!$MAPCFG->objExists($attrs['object_id']))
                        throw new NagVisException(l('The object does not exist.'));
                    $objId = $attrs['object_id'];

                    $func = 'handle_action_'.$attrs['cmd'];
                    if(!function_exists($func))
                        throw new NagVisException(l('Action handler not implemented.'));

                    $func($MAPCFG, $objId);

                break;
                
                case 'acknowledge':
                    $VIEW = new ViewAck();
                    $sReturn = json_encode(Array('code' => $VIEW->parse()));
                break;
            }
        }

        return $sReturn;
    }
}
?>
