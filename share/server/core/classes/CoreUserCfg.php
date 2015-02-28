<?php
/*****************************************************************************
 *
 * CoreUserCfg.php - Class for handling user/profile specific configurations
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
class CoreUserCfg {
    private $profilesDir;

    // Optional list of value types to be fixed
    private $types = Array(
      'sidebar'  => 'i',
      'header'   => 'b',
      'eventlog' => 'b',
    );

    public function __construct() {
        $this->profilesDir = cfg('paths', 'profiles');
    }

    public function doGet($onlyUserCfg = false) {
        global $AUTH, $AUTHORISATION;
        $opts = Array();
        if(!$AUTH->isAuthenticated())
            return $opts;

        if(!file_exists($this->profilesDir))
            return $opts;

        // Fetch all profile files to load
        $files = Array();
        if(!$onlyUserCfg)
            foreach($AUTHORISATION->getUserRoles($AUTH->getUserId()) AS $role)
                $files[] = $role['name'].'.profile';
        $files[] = $AUTH->getUser().'.profile';

        // Read all configurations and append to the option array
        foreach($files AS $file) {
            $f = $this->profilesDir.'/'.$file;
            if(!file_exists($f))
                continue;

            $a = json_decode(file_get_contents($f), true);
            if(!is_array($a))
                throw new NagVisException(l('Invalid data in "[FILE]".', Array('FILE' => $f)));

            $opts = array_merge($opts, $a);
        }

        return $opts;
    }

    public function doGetAsJson($onlyUserCfg = false) {
        return json_encode($this->doGet($onlyUserCfg));
    }

    public function doSet($opts) {
        global $CORE, $AUTH;
        $file = $this->profilesDir.'/'.$AUTH->getUser().'.profile';

        if(!$CORE->checkExisting(dirname($file), true) || !$CORE->checkWriteable(dirname($file), true))
            return false;

        $cfg = $this->doGet(true);

        foreach($opts AS $key => $value) {
            if(isset($this->types[$key]))
                $value = $this->fixType($value, $this->types[$key]);
            $cfg[$key] = $value;
        }

        $ret = file_put_contents($file, json_encode($cfg)) !== false;
        $CORE->setPerms($file);
        return $ret;
    }

    public function getValue($key, $default = null) {
        $opts = $this->doGet();
        return isset($opts[$key]) ? $opts[$key] : $default;
    }

    private function fixType($val, $type) {
        if($type == 'i')
            return (int) $val;
        elseif($type == 'b') {
            if($val == '1' || $val === 'true')
                return true;
            else
                return false;
        } else
            return $val;
    }
}
