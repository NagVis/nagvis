<?php
/*****************************************************************************
 *
 * CoreModUserMgmt.php - Core module for handling the user management tasks
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

class CoreModUserMgmt extends CoreModule
{
    /**
     * @param GlobalCore $CORE
     */
    public function __construct($CORE)
    {
        $this->sName = 'UserMgmt';

        $this->aActions = [
            'view'         => 'manage',
        ];
    }

    /**
     * @return false|string
     * @throws CoreAuthModNoSupport
     * @throws FieldInputError
     * @throws NagVisException
     */
    public function handleAction()
    {
        if ($this->offersAction($this->sAction)) {
            if ($this->sAction == 'view') {
                $VIEW = new ViewManageUsers();
                return json_encode(['code' => $VIEW->parse()]);
            }
        }

        return '';
    }
}
