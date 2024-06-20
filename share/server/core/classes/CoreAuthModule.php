<?php
/*******************************************************************************
 *
 * CoreAuthModule.php - Abstract definition of a CoreAuthModule
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
 * Abstract definition of a CoreAuthModule
 * All authentication modules should extend this class
 *
 * @author Lars Michelsen <lm@larsmichelsen.com>
 */
abstract class CoreAuthModule {
    /** @var array */
    protected static $aFeatures;

    /**
     * Returns a list of supported features
     *
     * @return	array
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getSupportedFeatures()
    {
        return self::$aFeatures;
    }

    /**
     * @param array $aData
     * @return void
     */
    abstract public function passCredentials($aData);

    /**
     * @param array $aData
     * @return void
     */
    abstract public function passNewPassword($aData);

    /**
     * @return bool
     */
    abstract public function changePassword();

    /**
     * @return array
     */
    abstract public function getCredentials();

    /**
     * @return bool
     */
    abstract public function isAuthenticated();

    /**
     * @return string
     */
    abstract public function getUser();

    /**
     * @return int
     */
    abstract public function getUserId();
}
