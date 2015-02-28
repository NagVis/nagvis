<?PHP
/*****************************************************************************
 *
 * ajax_error_handler.php - Ajax error handler for the NagVis ajax handlers
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
 * This is a custom error handling function for submitting PHP errors to the
 * ajax requesting frontend
 *
 * @author     Lars Michelsen <lars@vertical-visions.de>
 */

function nagvisException($OBJ) {
    try {
        if(get_class($OBJ) == 'NagVisException'
           || get_class($OBJ) == 'NagVisErrorException') {
            echo $OBJ;
        } else {
            echo "Error (".get_class($OBJ)."): ".$OBJ->getMessage();
        }

        die();
    } catch(Exception $e) {
        echo "Error: Unexpected Problem in Exception Handler!: ". $e->getMessage();        
        die();
    }
}

function nagvisExceptionErrorHandler($errno, $errstr, $errfile, $errline ) {
    // Use current error_reporting settings to skip unwanted errors
    if(!(error_reporting() & $errno))
        return false;
    
    throw new NagVisErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("nagvisExceptionErrorHandler");

// Enable custom error handling
if(function_exists('set_exception_handler')) {
    set_exception_handler('nagvisException');
}
?>
