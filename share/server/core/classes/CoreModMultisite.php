<?php
/*******************************************************************************
 *
 * CoreModMultisite.php - Core multisite  module to handle ajax requests
 *
 * Copyright (c) 2004-2013 NagVis Project (Contact: info@nagvis.org)
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
class CoreModMultisite extends CoreModule {
    public function __construct(GlobalCore $CORE) {
        $this->sName = 'Multisite';
        $this->CORE = $CORE;

        $this->aActions = Array(
            'getMaps' => REQUIRES_AUTHORISATION,
        );
    }

    public function handleAction() {
        if(!$this->offersAction($this->sAction))
            return '';

        switch($this->sAction) {
            case 'getMaps':
                if (cfg('global', 'multisite_snapin_layout') == 'tree') {
                    return $this->renderTree();
                } else {
                    return $this->renderTable();
                }
            break;
        }
    }

    private function renderTree() {
        $maps = array();
        $childs = array();
        foreach ($this->getMaps() as $map) {
            if($map['parent_map'] === '')
                $maps[$map['name']] = $map;
            else {
                if(!isset($childs[$map['parent_map']]))
                    $childs[$map['parent_map']] = Array();
                $childs[$map['parent_map']][$map['name']] = $map;
            }
        }

        $s = '<ul>'.$this->renderTreeNodes($maps, $childs).'</ul>';

        // FIXME: check_mk/tree_state.py?tree=nagvis holen
        // evaluieren
        // alles was auf off steht per toggle_foldable_container schließen
        return $s;
    }

    private function renderTreeNodes($maps, $childs) {
        $s = '';
        foreach($maps AS $map) {
            // this copies the foldable_container code provided in Check_MK htmllib
            // assume always open by default
            $s .= '<li>';
            if(isset($childs[$map['name']])) {
                $act = 'onclick="toggle_foldable_container(\'nagvis\', \''.$map['name'].'\')" '
                     . 'onmouseover="this.style.cursor=\'pointer\';" '
                     . 'onmouseout="this.style.cursor=\'auto\';"';

                $s .= '<img align=absbottom class="treeangle" id="treeimg.nagvis.'.$map['name'].'" '
                    . 'src="images/tree_90.png" '.$act.' />';
                $s .= '<b class="treeangle title" class=treeangle '.$act.'>'.$map['alias'].'</b><br>';
                    $s .= '<ul class="treeangle open" style="padding-left:0;" id="tree.nagvis.'.$map['name'].'">';
                    $s .= $this->renderTreeNodes($childs[$map['name']], $childs);
                    $s .= '</ul>';
            } else {
                $s .= '<a target="main" href="'.cfg('paths', 'htmlbase').'/index.php?mod=Map&act=view'
                    . '&show='.$map['name'].'">'.$map['alias'].'</a>';
            }
            $s .= '</li>';
        }
        return $s;
    }

    private function renderTable() {
        $code = '<table class="allhosts"><tbody>';
        foreach ($this->getMaps() as $map) {
            switch($map['summary_state']) {
                case 'OK':
                case 'UP':
                    $state = '0';
                    break;
                case 'WARNING':
                    $state = '1';
                    break;
                case 'CRITICAL':
                case 'DOWN':
                case 'UNREACHABLE':
                    $state = '2';
                    break;
                default:
                    $state = '3';
                    break;
            }

            $title = $map['summary_state'];

            if ($map['summary_in_downtime']) {
                $state .= ' stated';
                $title .= ' (Downtime)';
            }
            elseif ($map['summary_problem_has_been_acknowledged']) {
                $state .= ' statea';
                $title .= ' (Acknowledged)';
            }


            $code .= '<tr><td>';
            $code .= '<div class="statebullet state'.$state.'" title="'.$title.'">&nbsp;</div>';
            $code .= '<a href="'.cfg('paths', 'htmlbase').'/index.php?mod=Map&act=view&show='.$map['name'].'" ';
            $code .= 'class="link" target="main">'.$map['alias'].'</a>';
            $code .= '</td></tr>';
        }
        $code .= '</tbody></table>';
        return $code;
    }

    /**
     * Returns the NagVis maps available to the user as HTML formated string
     *
     * @return	String  HTML code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function getMaps() {
        global $_BACKEND, $AUTHORISATION;
        $aObjs = Array();
        foreach($this->CORE->getAvailableMaps() AS $object_id => $mapName) {
            if(!$AUTHORISATION->isPermitted('Map', 'view', $mapName))
                continue;

            $MAPCFG = new GlobalMapCfg($mapName);

            $config_error = null;
            $error = null;
            try {
                $MAPCFG->readMapConfig();
            } catch(MapCfgInvalid $e) {
                $config_error = $e->getMessage();
            } catch(Exception $e) {
                $error = $e->getMessage();
            }

            if($MAPCFG->getValue(0, 'show_in_lists') != 1 || $MAPCFG->getValue(0, 'show_in_multisite') != 1)
                continue;

            $MAP = new NagVisMap($MAPCFG, GET_STATE, !IS_VIEW);

            // Apply default configuration to object
            $objConf = $MAPCFG->getTypeDefaults('global');
            $objConf['type']              = 'map';
            $objConf['map_name']          = $MAPCFG->getName();
            $objConf['object_id']         = $object_id;
            // Enable the hover menu in all cases - maybe make it configurable
            $objConf['hover_menu']        = 0;
            $objConf['hover_childs_show'] = 0;
            $objConf['hover_template']    = 'default';
            $objConf['parent_map']        = $MAPCFG->getValue(0, 'parent_map');
            unset($objConf['alias']);

            $MAP->MAPOBJ->setConfiguration($objConf);

            if($config_error !== null) {
                $MAP->MAPOBJ->clearMembers();
                $MAP->MAPOBJ->setState(array(
                    ERROR,
                    l('Map Configuration Error: ').$config_error,
                    null,
                    null
                ));
                $MAP->MAPOBJ->fetchIcon();
            } elseif($error !== null) {
                $MAP->MAPOBJ->clearMembers();
                $MAP->MAPOBJ->setState(array(
                    ERROR,
                    l('Error: ').$error,
                    null,
                    null
                ));
                $MAP->MAPOBJ->fetchIcon();
            } elseif($MAP->MAPOBJ->checkMaintenance(0)) {
                $MAP->MAPOBJ->fetchIcon();
            } else {
                $MAP->MAPOBJ->clearMembers();
                $MAP->MAPOBJ->setState(array(
                    UNKNOWN,
                    l('mapInMaintenance'),
                    null,
                    null
                ));
                $MAP->MAPOBJ->fetchIcon();
            }

            $MAP->MAPOBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
            $aObjs[] = $MAP->MAPOBJ;
        }

        $_BACKEND->execute();

        $aMaps = Array();
        foreach($aObjs AS $MAP) {
            $MAP->applyState();
            $MAP->fetchIcon();

            $aMaps[] = $MAP->getObjectInformation();
        }

        usort($aMaps, Array('GlobalCore', 'cmpAlias'));
        return $aMaps;
    }
}
?>
