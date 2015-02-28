<?php
/*****************************************************************************
 *
 * NagVisContextMenu.php - Class for handling the context menus
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
 *****************************************************************************/

/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisContextMenu {
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
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct($CORE, $templateName, $OBJ = NULL) {
        $this->CORE = $CORE;
        $this->OBJPAGE = $OBJ;
        $this->templateName = $templateName;

        $this->pathHtmlBase     = cfg('paths', 'htmlbase');
        $this->pathTemplateFile = path('sys', '', 'templates', $this->templateName.'.context.html');

        $this->CACHE = new GlobalFileCache($this->pathTemplateFile,
                                           cfg('paths','var').'context-'.$this->templateName.'-'.curLang().'.cache');

        // Only use cache when there is
        // a) Some valid cache file
        // b) Some valid main configuration cache file
        // c) This cache file newer than main configuration cache file
        if($this->CACHE->isCached() !== -1
          && $this->CORE->getMainCfg()->isCached() !== -1
          && $this->CACHE->isCached() >= $this->CORE->getMainCfg()->isCached()) {
            $this->code = $this->CACHE->getCache();
        } else {
            // Read the contents of the template file
            if($this->readTemplate()) {
                // The static macros should be replaced before caching
                $this->replaceStaticMacros();

                // Build cache for the template
                $this->CACHE->writeCache($this->code, 1);
            }
        }
    }

    /**
     * readTemplate
     *
     * Reads the contents of the template file
     *
     * @return	Boolean		Result
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    function readTemplate() {
        if($this->checkTemplateReadable(1)) {
            $this->code = file_get_contents($this->pathTemplateFile);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * replaceStaticMacros
     *
     * Replaces static macros like paths and language strings in template code
     *
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function replaceStaticMacros() {
        // Replace the static macros (language, paths)
        if(strpos($this->code,'[lang_confirm_delete]') !== FALSE) {
            $this->code = str_replace('[lang_confirm_delete]', l('confirmDelete'), $this->code);
        }

        if(strpos($this->code,'[lang_connect_by_ssh]') !== FALSE) {
            $this->code = str_replace('[lang_connect_by_ssh]',l('contextConnectBySsh'),$this->code);
        }

        if(strpos($this->code,'[lang_refresh_status]') !== FALSE) {
            $this->code = str_replace('[lang_refresh_status]',l('contextRefreshStatus'),$this->code);
        }

        if(strpos($this->code,'[lang_reschedule_next_check]') !== FALSE) {
            $this->code = str_replace('[lang_reschedule_next_check]',l('contextRescheduleNextCheck'),$this->code);
        }

        if(strpos($this->code,'[lang_schedule_downtime]') !== FALSE) {
            $this->code = str_replace('[lang_schedule_downtime]',l('contextScheduleDowntime'),$this->code);
        }

        if(strpos($this->code,'[lang_ack]') !== FALSE)
            $this->code = str_replace('[lang_ack]', l('Acknowledge'), $this->code);

        if(strpos($this->code,'[lang_clone]') !== FALSE)
            $this->code = str_replace('[lang_clone]', l('Clone object'), $this->code);

        if(strpos($this->code,'[lang_lock]') !== FALSE)
            $this->code = str_replace('[lang_lock]', l('Lock'), $this->code);

        if(strpos($this->code,'[lang_unlock]') !== FALSE)
            $this->code = str_replace('[lang_unlock]', l('Unlock'), $this->code);

        if(strpos($this->code,'[lang_modify]') !== FALSE)
            $this->code = str_replace('[lang_modify]', l('Modify object'), $this->code);

        if(strpos($this->code,'[lang_delete]') !== FALSE)
            $this->code = str_replace('[lang_delete]', l('Delete object'), $this->code);

        if(strpos($this->code,'[lang_delete_confirm]') !== FALSE)
            $this->code = str_replace('[lang_delete_confirm]', l('Really delete the object?'), $this->code);

        if(strpos($this->code,'[lang_toggle_line_mid]') !== FALSE)
            $this->code = str_replace('[lang_toggle_line_mid]', l('Lock/Unlock line middle'), $this->code);

        if(strpos($this->code,'[html_base]') !== FALSE) {
            $this->code = str_replace('[html_base]',cfg('paths','htmlbase'),$this->code);
        }

        if(strpos($this->code,'[html_templates]') !== FALSE) {
            $this->code = str_replace('[html_templates]', path('html', 'global', 'templates'), $this->code);
        }

        if(strpos($this->code,'[html_template_images]') !== FALSE) {
            $this->code = str_replace('[html_template_images]', path('html', 'global', 'templateimages'), $this->code);
        }

        if(strpos($this->code,'[lang_make_root]') !== FALSE) {
            $this->code = str_replace('[lang_make_root]', l('Make root'), $this->code);
        }

        if(strpos($this->code,'[lang_action_rdp]') !== FALSE) {
            $this->code = str_replace('[lang_action_rdp]', l('Connect (RDP)'), $this->code);
        }

        if(strpos($this->code,'[lang_action_ssh]') !== FALSE) {
            $this->code = str_replace('[lang_action_ssh]', l('Connect (SSH)'), $this->code);
        }

        if(strpos($this->code,'[lang_action_http]') !== FALSE) {
            $this->code = str_replace('[lang_action_http]', l('Connect (HTTP)'), $this->code);
        }

        if(strpos($this->code,'[lang_action_https]') !== FALSE) {
            $this->code = str_replace('[lang_action_https]', l('Connect (HTTPS)'), $this->code);
        }
    }

    /**
     * Print the HTML code
     *
     * return   String  HTML Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __toString () {
        return $this->code;
    }

    /**
     * PRIVATE checkTemplateReadable()
     *
     * Checks if the requested template file is readable
     *
     * @param		Boolean		Switch for enabling/disabling error messages
     * @return	Boolean		Check Result
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkTemplateReadable($printErr) {
        return GlobalCore::getInstance()->checkReadable($this->pathTemplateFile, $printErr);
    }

    /**
     * PRIVATE checkTemplateExists()
     *
     * Checks if the requested template file exists
     *
     * @param		Boolean		Switch for enabling/disabling error messages
     * @return	Boolean		Check Result
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkTemplateExists($printErr) {
        return GlobalCore::getInstance()->checkExisting($this->pathTemplateFile, $printErr);
    }

    public function getCssFile() {
        return path('html', 'global', 'templates', $this->templateName.'.context.css');
    }
}
?>
