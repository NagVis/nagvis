<?php
/*****************************************************************************
 *
 * NagVisHoverMenu.php - Class for handling the hover menus
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
 *****************************************************************************/

/**
 * @author	Lars Michelsen <lm@larsmichelsen.com>
 */
class NagVisHoverMenu {
    private $CORE;
    private $OBJPAGE;
    private $CACHE;

    private $templateName;
    private $pathHtmlBase;
    private $pathTemplateFile;

    private $code;

    /**
     * Class Constructor
     *
     * @param 	GlobalCore 	$CORE
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function __construct($CORE, $templateName, $OBJ = null) {
        $this->CORE = $CORE;
        $this->OBJPAGE = $OBJ;

        $this->templateName = $templateName;

        $this->pathHtmlBase     = cfg('paths', 'htmlbase');
        $this->pathTemplateFile = path('sys', '', 'templates', $this->templateName . '.hover.html');

        // Simply skip processing with an invalid template file name
        if($this->pathTemplateFile === '') {
            return;
        }

        $this->CACHE = new GlobalFileCache($this->pathTemplateFile, cfg('paths', 'var') . 'hover-' . $this->templateName . '-' . curLang() . '.cache');

        // Only use cache when there is
        // a) Some valid cache file
        // b) Some valid main configuration cache file
        // c) This cache file newer than main configuration cache file
        if($this->CACHE->isCached() !== -1
            && $this->CORE->getMainCfg()->isCached() !== -1
            && $this->CACHE->isCached() >= $this->CORE->getMainCfg()->isCached()) {
            $this->code = $this->CACHE->getCache();
        } elseif($this->readTemplate()) {
            // Read the contents of the template file
            // The static macros should be replaced before caching
            $this->replaceStaticMacros();

            // Build cache for the template
            $this->CACHE->writeCache($this->code, 1);
        }
    }

    /**
     * readHoverTemplate
     *
     * Reads the contents of the hover template file
     *
     * @return    bool        HTML Code for the hover menu
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    private function readTemplate() {
        if($this->checkTemplateReadable(1)) {
            $this->code =  file_get_contents($this->pathTemplateFile);
            return true;
        } else {
            return false;
        }
    }

    /**
     * replaceStaticMacros
     *
     * Replaces static macros like paths and language strings in template code
     *
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    private function replaceStaticMacros() {
        // Replace the static macros (language, paths)
        if(strpos($this->code, '[lang_alias]') !== false) {
            $this->code = str_replace('[lang_alias]', l('alias'), $this->code);
        }

        if(strpos($this->code, '[lang_address]') !== false) {
            $this->code = str_replace('[lang_address]', l('address'), $this->code);
        }

        if(strpos($this->code, '[lang_state]') !== false) {
            $this->code = str_replace('[lang_state]', l('state'), $this->code);
        }

        if(strpos($this->code, '[lang_summary_state]') !== false) {
            $this->code = str_replace('[lang_summary_state]', l('summaryState'), $this->code);
        }

        if(strpos($this->code, '[lang_output]') !== false) {
            $this->code = str_replace('[lang_output]', l('output'), $this->code);
        }

        if(strpos($this->code, '[lang_perfdata]') !== false) {
            $this->code = str_replace('[lang_perfdata]', l('perfdata'), $this->code);
        }

        if(strpos($this->code, '[lang_summary_output]') !== false) {
            $this->code = str_replace('[lang_summary_output]', l('summaryOutput'), $this->code);
        }

        if(strpos($this->code, '[lang_overview]') !== false) {
            $this->code = str_replace('[lang_overview]', l('overview'), $this->code);
        }

        if(strpos($this->code, '[lang_instance]') !== false) {
            $this->code = str_replace('[lang_instance]', l('instance'), $this->code);
        }

        if(strpos($this->code, '[lang_next_check]') !== false) {
        $this->code = str_replace('[lang_next_check]', l('nextCheck'), $this->code);
        }

        if(strpos($this->code, '[lang_last_check]') !== false) {
            $this->code = str_replace('[lang_last_check]', l('lastCheck'), $this->code);
        }

        if(strpos($this->code, '[lang_state_type]') !== false) {
            $this->code = str_replace('[lang_state_type]', l('stateType'), $this->code);
        }

        if(strpos($this->code, '[lang_current_attempt]') !== false) {
            $this->code = str_replace('[lang_current_attempt]', l('currentAttempt'), $this->code);
        }

        if(strpos($this->code, '[lang_last_state_change]') !== false) {
            $this->code = str_replace('[lang_last_state_change]', l('lastStateChange'), $this->code);
        }

        if(strpos($this->code, '[lang_state_duration]') !== false) {
            $this->code = str_replace('[lang_state_duration]', l('stateDuration'), $this->code);
        }

        if(strpos($this->code, '[lang_service_description]') !== false) {
            $this->code = str_replace('[lang_service_description]', l('servicename'), $this->code);
        }

        if(strpos($this->code, '[lang_notes]') !== false) {
            $this->code = str_replace('[lang_notes]', l('notes'), $this->code);
        }

        if(strpos($this->code, '[lang_last_status_refresh]') !== false) {
            $this->code = str_replace('[lang_last_status_refresh]', l('lastStatusRefresh'), $this->code);
        }

        if(strpos($this->code, '[lang_tags]') !== false) {
            $this->code = str_replace('[lang_tags]', l('Tags'), $this->code);
        }

        if(strpos($this->code, '[html_base]') !== false) {
            $this->code = str_replace('[html_base]', cfg('paths', 'htmlbase'), $this->code);
        }

        if(strpos($this->code, '[html_templates]') !== false) {
            $this->code = str_replace('[html_templates]', path('sys', 'global', 'templates'), $this->code);
        }

        if(strpos($this->code, '[html_template_images]') !== false) {
            $this->code = str_replace('[html_template_images]', path('html', 'global', 'templateimages'), $this->code);
        }
    }

    /**
     * Print the HTML code
     *
     * return   String  HTML Code
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function __toString () {
        return $this->code;
    }

    /**
     * PRIVATE checkTemplateReadable()
     *
     * Checks if the requested hover template file is readable
     *
     * @param		bool $printErr Switch for enabling/disabling error messages
     * @return	bool		Check Result
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    private function checkTemplateReadable($printErr) {
        return GlobalCore::getInstance()->checkReadable($this->pathTemplateFile, $printErr);
    }

    /**
     * PRIVATE checkTemplateExists()
     *
     * Checks if the requested hover template file exists
     *
     * @param		bool $printErr Switch for enabling/disabling error messages
     * @return	bool		Check Result
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    private function checkTemplateExists($printErr) {
        return GlobalCore::getInstance()->checkExisting($this->pathTemplateFile, $printErr);
    }

    public function getCssFile() {
        return path('html', 'global', 'templates', $this->templateName . '.hover.css');
    }
}

