<?php
/*******************************************************************************
 *
 * CoreModMultisite.php - Core multisite  module to handle ajax requests
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
        foreach ($this->getMapsCached() as $map) {
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
            $map_url = cfg('paths', 'htmlbase').'/index.php?mod=Map&act=view&show='.$map['name'];
            if(isset($childs[$map['name']])) {
                $act = 'onclick="toggle_foldable_container(\'nagvis\', \''.$map['name'].'\')" '
                     . 'onmouseover="this.style.cursor=\'pointer\';" '
                     . 'onmouseout="this.style.cursor=\'auto\';"';

                $s .= '<img align=absbottom class="treeangle" id="treeimg.nagvis.'.$map['name'].'" '
                    . 'src="images/tree_90.png" '.$act.' />';
                $s .= '<a href="'.$map_url.'" target="main"><b class="treeangle title" class=treeangle>';
                $s .= $map['alias'];
                $s .= '</b></a><br>';
                $s .= '<ul class="treeangle open" style="padding-left:0;" id="tree.nagvis.'.$map['name'].'">';
                $s .= $this->renderTreeNodes($childs[$map['name']], $childs);
                $s .= '</ul>';
            } else {
                $s .= '<a target="main" href="'.$map_url.'">'.$map['alias'].'</a>';
            }
            $s .= '</li>';
        }
        return $s;
    }

    private function renderTable() {
        $code = '<table class="allhosts"><tbody>';
        foreach ($this->getMapsCached() as $map) {
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

            if ($map['summary_stale']) {
                $state .= ' stale';
                $title .= ' (Stale)';
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

    // Wraps the getMaps() function by applying a short livetime cache based
    // on the maps a user can access. This respects the map access permissions.
    // The cache optimizes the case where a lot of users having the Check_MK
    // NagVis maps snapin open at the same time while most of the users have
    // equal permissions.
    private function getMapsCached() {
        $maps = $this->CORE->getPermittedMaps();
        $cache_file = cfg('paths','var').'snapin-'.md5(json_encode(array_keys($maps))).'-'.CONST_VERSION.'.cache';
        $CACHE = new GlobalFileCache(array(), $cache_file);
        $cached = $CACHE->isCached();

        if ($cached != -1 && time() - $cached < 15) {
            return $CACHE->getCache();
        } else {
            $result = $this->getMaps($maps);
            $CACHE->writeCache($result);
            return $result;
        }
    }

    // Gathers an array of maps and their states to be shown to the user
    // in the multisite snapin
    private function getMaps($maps) {
        global $_BACKEND, $AUTHORISATION;
        $aObjs = Array();
        foreach($maps AS $object_id => $mapName) {
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
                    null,
                    null,
                ));
                $MAP->MAPOBJ->fetchIcon();
            } elseif($error !== null) {
                $MAP->MAPOBJ->clearMembers();
                $MAP->MAPOBJ->setState(array(
                    ERROR,
                    l('Error: ').$error,
                    null,
                    null,
                    null,
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
