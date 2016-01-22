<?php
/*****************************************************************************
 *
 * ViewChangePassword.php - Class for handling the change password page
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

class ViewChangePassword {
    private $error = null;

    private function changeForm() {
        global $CORE, $AUTH;
        if (is_action()) {
            try {
                $user = $AUTH->getUser();

                $password_old = post('password_old');
                if (!$password_old)
                    throw new FieldInputError('password_old', l('You need to specify the old password.'));

                $password_new1 = post('password_new1');
                if (!$password_new1)
                    throw new FieldInputError('password_new1', l('You need to specify the new password.'));

                $password_new2 = post('password_new2');
                if (!$password_new2)
                    throw new FieldInputError('password_new2', l('You need to specify to confirm the new password.'));

                if ($password_new1 != $password_new2)
                    throw new FieldInputError('password_new1', l('The new passwords do not match.'));

                if ($password_old == $password_new1)
                    throw new FieldInputError('password_new1', l('The new and old passwords are equal. Won\'t change anything.'));

                // Set new passwords in authentication module, then change it
                $AUTH->passNewPassword(array(
                    'user'        => $user,
                    'password'    => $password_old,
                    'passwordNew' => $password_new1,
                ));
                if (!$AUTH->changePassword())
                    throw new NagVisException(l('Your password could not be changed.'));
                success(l('Your password has been changed.'));
                js('setTimeout(popupWindowClose, 1000);'); // close window after 1 sec
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

        js_form_start('change_password');
        echo '<table class="mytable">';
        echo '<tr><td class="tdlabel">'.l('Old password').'</td>';
        echo '<td class="tdfield">';
        password('password_old');
        echo '</td></tr>';
        echo '<tr><td class="tdlabel">'.l('New password').'</td>';
        echo '<td class="tdfield">';
        password('password_new1');
        echo '</td></tr>';
        echo '<tr><td class="tdlabel">'.l('New password (confirm)').'</td>';
        echo '<td class="tdfield">';
        password('password_new2');
        echo '</td></tr>';
        echo '</table>';
        js('try{document.getElementById(\'password_old\').focus();}catch(e){}');

        submit(l('Change password'));
        form_end();
    }

    public function parse() {
        ob_start();
        $this->changeForm();
        return ob_get_clean();
    }
}
?>
