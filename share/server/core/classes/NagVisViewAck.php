<?php
/*****************************************************************************
 *
 * NagVisViewAck.php - Class for handling the Ack dialog
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
class NagVisViewAck {
    private $errors = array();
    private $MAPCFG = null;

    public function __construct($MAPCFG) {
        $this->MAPCFG = $MAPCFG;
    }

    public function parse($attrs, $err, $success) {
        $s = '';

        global $_BACKEND;
        $backendIds = $this->MAPCFG->getValue($attrs['object_id'], 'backend_id');
        foreach ($backendIds AS $backendId) {
            if(!$_BACKEND->checkBackendFeature($backendId, 'actionAcknowledge', false)) {
                return '<div class=err>'
                 .l('The requested feature is not available for this backend. The MKLivestatus backend supports this feature.')
                 .'</div>';
            }
        }
        
        if($err)
            $this->errors[$err->field] = array($err->msg);

        if($success) {
            $s .= '<div class=success>'.$success.'</div>'
                 .'<script type="text/javascript">'
                 .'window.setTimeout(function() {'
                 .'popupWindowClose(); refreshMapObject(null, \''.$attrs['object_id'].'\');}, 2000);'
                 .'</script>';
        }

        $s .= $this->form_start('ack', cfg('paths', 'htmlbase').'/server/core/ajax_handler.php?mod=Action&act=acknowledge');
        $s .= $this->hidden('map', $attrs['map']);
        $s .= $this->hidden('object_id', $attrs['object_id']);
        $s .= $this->input(l('Comment'), 'comment');
        $s .= $this->checkbox(l('Sticky'),            'sticky',  cfg('global', 'dialog_ack_sticky'));
        $s .= $this->checkbox(l('Send notification'), 'notify',  cfg('global', 'dialog_ack_notify'));
        $s .= $this->checkbox(l('Persist comment'),   'persist', cfg('global', 'dialog_ack_persist'));
        $s .= $this->submit(l('Acknowledge'));
        $s .= $this->form_end();

        // Errors left?
        if(count($this->errors) > 0) {
            foreach($this->errors AS $attr => $errors)
                foreach($errors AS $err)
                    $s .= '<div class=err>'.$attr.': ' . $err . '</div>';
        }

        $s .= '<script type="text/javascript">'
             .'try{document.getElementById("comment").focus();}catch(e){}'
             .'</script>';

        return $s;
    }

    private function hidden($name, $value) {
        return '<input type=hidden name="'.$name.'" value="'.$value.'" />';
    }

    private function form_end() {
        return '</form>';
    }

    private function form_start($name, $target) {
        return '<form name="'.$name.'" id="'.$name.'" '
              .'action="javascript:submitFrontendForm2(\''.$target.'\', \''.$name.'\');" method="post">';
    }

    private function submit($label) {
        return '<br><input class="submit" type="submit" name="submit" value="'.$label.'" />';
    }

    private function input($label, $name) {
        return '<label>'.$label.'<br />'
            .'    <input type="text" name="'.$name.'" id="'.$name.'" /></label>'
            .$this->fieldErrors($name);
    }

    private function checkbox($label, $name, $checked = false) {
        if((isset($_REQUEST[$name]) && $_REQUEST[$name] == '1')
            || (!isset($_REQUEST[$name]) && $checked))
            $checked = ' checked';
        else
            $checked = '';

        return '<br><input type="checkbox" name="'.$name.'"'.$checked.' value=1 />'
              .'<label for="'.$name.'">'.$label.'</label>'.$this->fieldErrors($name);
    }

    private function fieldErrors($name) {
        $s = '';

        if(isset($this->errors[$name])) {
            foreach($this->errors[$name] AS $err)
                $s .= '<div class=err>' . $err . '</div>';
            unset($this->errors[$name]);
        }

        return $s;
    }
}
?>
