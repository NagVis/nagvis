<?php
/*****************************************************************************
 *
 * ViewAck.php - Class for handling the Ack dialog
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

class ViewAck {
    private $error = null;

    public function parse() {
        global $CORE, $_BACKEND, $AUTH;

        ob_start();

        $map = req('map');
        if (!$map || count($CORE->getAvailableMaps('/^'.$map.'$/')) == 0)
            throw new NagVisException(l('Please provide a valid map name.'));

        $object_id = req('object_id');
        if (!$object_id || !preg_match(MATCH_OBJECTID, $object_id))
            throw new NagVisException(l('Please provide a valid object id.'));

        $MAPCFG = new GlobalMapCfg($map);
        $MAPCFG->skipSourceErrors();
        $MAPCFG->readMapConfig();

        if (!$MAPCFG->objExists($object_id))
            throw new NagVisException(l('The object does not exist.'));

        $backendIds = $MAPCFG->getValue($object_id, 'backend_id');
        foreach ($backendIds AS $backendId) {
            if(!$_BACKEND->checkBackendFeature($backendId, 'actionAcknowledge', false)) {
                return '<div class=err>'
                 .l('The requested feature is not available for this backend. '
                   .'The MKLivestatus backend supports this feature.')
                 .'</div>';
            }
        }

        if (is_action()) {
            try {
                $type = $MAPCFG->getValue($object_id, 'type');
                if ($type == 'host')
                    $spec = $MAPCFG->getValue($object_id, 'host_name');
                else
                    $spec = $MAPCFG->getValue($object_id, 'host_name')
                            .';'.$MAPCFG->getValue($object_id, 'service_description');

                $comment = post('comment');
                if (!$comment)
                    throw new FieldInputError('comment', l('You need to provide a comment.'));

                $sticky  = get_checkbox('sticky');
                $notify  = get_checkbox('notify');
                $persist = get_checkbox('persist');

                // Now send the acknowledgement
                foreach ($backendIds AS $backendId) {
                    $BACKEND = $_BACKEND->getBackend($backendId);
                    $BACKEND->actionAcknowledge($type, $spec, $comment,
                                                $sticky, $notify, $persist, $AUTH->getUser());
                }

                success(l('The problem has been acknowledged.'));
                 js('window.setTimeout(function() {'
                   .'popupWindowClose(); refreshMapObject(null, \''.$object_id.'\');}, 2000);');
            } catch (FieldInputError $e) {
                form_error($e->field, $e->msg);
            } catch (NagVisException $e) {
                form_error(null, $e->message());
            } catch (Exception $e) {
                if (isset($e->msg))
                    form_error(null, $e->msg);
                else
                    throw $e;
            }
        }
        echo $this->error;

        js_form_start('acknowledge');
        echo '<label>'.l('Comment');
        input('comment');
        echo '</label>';
        echo '<label>';
        checkbox('sticky', cfg('global', 'dialog_ack_sticky'));
        echo l('Sticky').'</label>';
        echo '<label>';
        checkbox('notify', cfg('global', 'dialog_ack_notify'));
        echo l('Notify contacts').'</label>';
        echo '<label>';
        checkbox('persist', cfg('global', 'dialog_ack_persist'));
        echo l('Persist comment').'</label>';
        submit(l('Acknowledge'));
        form_end();
        focus('comment');

        return ob_get_clean();
    }
}
?>
