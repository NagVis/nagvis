<?php
/*******************************************************************************
 *
 * CoreAuthorisationModMySQL.php - Authorsiation module based on MySQL
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

class CoreAuthorisationModMySQL extends CoreAuthorisationModPDO {
    public function getConfig() {
        return array(
            'driver' => 'mysql',
            'params' => array(
                'dbhost' => cfg('auth_mysql', 'dbhost'),
                'dbport' => cfg('auth_mysql', 'dbport'),
                'dbname' => cfg('auth_mysql', 'dbname'),
            ),
            'username' => cfg('auth_mysql', 'dbuser'),
            'password' => cfg('auth_mysql', 'dbpass'),
        );
    }
}
?>
