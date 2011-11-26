<?php
/*******************************************************************************
 *
 * CoreModMultisite.php - Core multisite  module to handle ajax requests
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
    private $BACKEND = null;

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'Multisite';
        $this->CORE = $CORE;

        $this->aActions = Array(
            'getMaps' => REQUIRES_AUTHORISATION,
        );
    }

    public function handleAction() {
        $sReturn = '';

        if(!$this->offersAction($this->sAction))
            return '';

        $this->BACKEND = new CoreBackendMgmt($this->CORE);

        switch($this->sAction) {
            case 'getMaps':
                // Initialize template system
                $TMPL = New CoreTemplateSystem($this->CORE);
                $TMPLSYS = $TMPL->getTmplSys();

                $aData = Array(
                    'htmlBase'  => cfg('paths', 'htmlbase'),
                    'maps'      => $this->getMaps(),
                );

                // Build page based on the template file and the data array
                $sReturn = $TMPLSYS->get($TMPL->getTmplFile('default', 'multisiteMaps'), $aData);
            break;
        }

        return $sReturn;
    }

    /**
     * Returns the NagVis maps available to the user as HTML formated string
     *
     * @return	String  HTML code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function getMaps() {
        global $AUTHORISATION;
        $aObjs = Array();
        foreach($this->CORE->getAvailableMaps() AS $object_id => $mapName) {
            if(!$AUTHORISATION->isPermitted('Map', 'view', $mapName))
                continue;

            $map = Array();
            $map['type'] = 'map';

            $MAPCFG = new NagVisMapCfg($this->CORE, $mapName);

            try {
                $MAPCFG->readMapConfig();
            } catch(MapCfgInvalid $e) {
                $map['configError'] = true;
                $map['configErrorMsg'] = $e->getMessage();
            }

            if($MAPCFG->getValue(0, 'show_in_lists') != 1 || $MAPCFG->getValue(0, 'show_in_multisite') != 1)
                continue;

            $MAP = new NagVisMap($this->CORE, $MAPCFG, $this->BACKEND, GET_STATE, !IS_VIEW);

            // Apply default configuration to object
            $objConf = $MAPCFG->getTypeDefaults('global');
            $objConf['type']              = 'map';
            $objConf['map_name']          = $MAPCFG->getName();
            $objConf['object_id']         = $object_id;
            // Enable the hover menu in all cases - maybe make it configurable
            $objConf['hover_menu']        = 1;
            $objConf['hover_childs_show'] = 1;
            $objConf['hover_template']    = 'default';
            unset($objConf['alias']);

            $MAP->MAPOBJ->setConfiguration($objConf);

            if(isset($map['configError'])) {
                $map['overview_class']  = 'error';
                $map['overview_url']    = 'javascript:alert(\''.$map['configErrorMsg'].'\');';
                $map['summary_output']  = l('Map Configuration Error: '.$map['configErrorMsg']);

                $MAP->MAPOBJ->clearMembers();
                $MAP->MAPOBJ->setSummaryState('ERROR');
                $MAP->MAPOBJ->fetchIcon();
            } elseif($MAP->MAPOBJ->checkMaintenance(0)) {
                $MAP->MAPOBJ->fetchIcon();

                $map['overview_url']    = cfg('paths', 'htmlbase').'/index.php?mod=Map&act=view&show='.$mapName;
                $map['overview_class']  = '';
                $map['summary_output']  = $MAP->MAPOBJ->getSummaryOutput();
            } else {
                $map['overview_class']  = 'disabled';
                $map['overview_url']    = 'javascript:alert(\''.l('mapInMaintenance').'\');';
                $map['summary_output']  = l('mapInMaintenance');

                $MAP->MAPOBJ->clearMembers();
                $MAP->MAPOBJ->setSummaryState('UNKNOWN');
                $MAP->MAPOBJ->fetchIcon();
            }

            $MAP->MAPOBJ->queueState(GET_STATE, GET_SINGLE_MEMBER_STATES);
            $aObjs[] = Array($MAP->MAPOBJ, $map);
        }

        $this->BACKEND->execute();

        $aMaps = Array();
        foreach($aObjs AS $aObj) {
            $aObj[0]->applyState();
            $aObj[0]->fetchIcon();

            $aMaps[] = $aObj[0]->getObjectInformation();
        }

        usort($aMaps, Array('GlobalCore', 'cmpAlias'));
        return $aMaps;
    }
}
?>
