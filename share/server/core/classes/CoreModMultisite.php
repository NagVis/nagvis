<?php
/*******************************************************************************
 *
 * CoreModMultisite.php - Core multisite  module to handle ajax requests
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

/**
 * @author Lars Michelsen <lm@larsmichelsen.com>
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

        $maps = array();

        switch($this->sAction) {
            case 'getMaps':
                if (cfg('global', 'multisite_snapin_layout') == 'tree') {
                    $maps = array(
                        "type" => "tree",
                        "maps" => $this->renderTree(),
                    );
                } else {
                    $maps = array(
                        "type" => "table",
                        "maps" => $this->renderTable(),
                    );
                }
            break;
        }

        return json_encode($maps);
    }

    private function renderTree() {
        $maps = array();
        $childs = array();
        foreach ($this->getMapsCached() as $map) {
            if($map['parent_map'] === '')
                $maps[$map['name']] = $this->getMapForMultisite($map);
            else {
                if(!isset($childs[$map['parent_map']]))
                    $childs[$map['parent_map']] = Array();
                $childs[$map['parent_map']][$map['name']] = $this->getMapForMultisite($map);
            }
        }
        return array(
            "maps" => $maps,
            "childs" => $childs,
        );
    }

    private function renderTable() {
        $maps = array();
        foreach ($this->getMapsCached() as $map) {
            $maps[] = $this->getMapForMultisite($map);
        }
        return $maps;
    }

    private function getMapForMultisite($map) {
        return array(
            "name" => $map["name"],
            "title" => $map['summary_state'],
            "alias" => $map['alias'],
            "url" => cfg('paths', 'htmlbase').'/index.php?mod=Map&act=view&show='.$map['name'],
            "summary_state" => $map["summary_state"],
            "summary_in_downtime" => $map['summary_in_downtime'],
            "summary_problem_has_been_acknowledged" => $map['summary_problem_has_been_acknowledged'],
            "summary_stale" => $map['summary_stale'],
        );
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

            // Apply mulitsite snapin related configuration to object
            $objConf = array(
                'type'              => 'map',
                'map_name'          => $MAPCFG->getName(),
                'object_id'         => $object_id,
                // Enable the hover menu in all cases - maybe make it configurable
                'hover_menu'        => 0,
                'hover_childs_show' => 0,
                'hover_template'    => 'default',
                'parent_map'        => $MAPCFG->getValue(0, 'parent_map'),
                // Enforce std_big iconset - don't use map default iconset
                'iconset'           => 'std_big',
                'icon_size'         => array(22),
            );
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
