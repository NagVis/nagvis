<?php
/*****************************************************************************
 *
 * autoload.php - Class for defining the autoload method for NagVis
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
 * Load required files for NagVis. This autoloader has no compatibility
 * problem with other autoloaders from external code
 *
 * @param   String  Name of the requested class
 * @author 	Lars Michelsen <lars@vertical-visions.de>
 */
function NagVisAutoload($sClass) {
	if(substr($sClass, 0, 8) === 'Frontend' 
	   || substr($sClass, 0, 3) === 'Wui' 
	   || substr($sClass, 0, 4) === 'Core' 
	   || substr($sClass, 0, 4) === 'View'
	   || substr($sClass, 0, 6) === 'NagVis'
	   || substr($sClass, 0, 6) === 'Nagios' 
	   || substr($sClass, 0, 6) === 'Global') {
		require($sClass.'.php');
		return true;
	} else {
		return false;
	}
}

spl_autoload_register('NagVisAutoload');

/**
 * loads all files located in core/functions directory. This directory
 * might contain custom functions which extend NagVis in some way.
 */
$dir = '../../server/core/functions/';
if ($handle = opendir($dir)) {
    while (false !== ($file = readdir($handle))) {
        if (preg_match(MATCH_PHP_FILE, $file)
            && $file != 'autoload.php'
            && $file != 'core.php'
            && $file != 'index.php') {
            require($dir.$file);
        }
    }
    closedir($handle);
}

?>
