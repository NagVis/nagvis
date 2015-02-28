<?php
/*****************************************************************************
 *
 * GlobalBackground.php - Class for global background image handling
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
class GlobalBackground {
    protected $image;
    protected $path;
    protected $webPath;
    protected $type;

    public function __construct($image) {
        $this->image = $image;

        $this->fetchPath();
    }

    /**
     * Gets the name of the image file
     *
     * @return	String File Name
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function getFileName() {
        return $this->image;
    }

    /**
     * Gets the locationtype of the file
     *
     * @return	String File Name
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getFileType() {
        return $this->type;
    }

    /**
     * Fetches the path and saves it on initial load
     *
     * @return	String File Name
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function fetchPath() {
        if($this->getFileName() != '' && $this->getFileName() != 'none') {
            // Extract url when used to show an url
            if(preg_match('/^\[(http.*)\]$/', $this->getFileName(), $match) > 0) {
                $this->type = 'url';

                $this->path = $match[1];
                $this->webPath = $match[1];
            } else {
                $this->type = 'local';

                $this->path    = path('sys', '',        'backgrounds', $this->getFileName());
                $this->webPath = path('html', 'global', 'backgrounds', $this->getFileName());
            }
        } else {
            $this->type = 'none';

            $this->path = '';
            $this->webPath = '';
        }
    }

    /**
     * Gets the background file
     *
     * @param   Boolean Get web path or alternatively the physical path
     * @return  String  HTML Path to background file
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getFile($bWebPath = true) {
        if($bWebPath)
            return $this->webPath;
        else
            return $this->path;
    }

    /**
     * Checks for existing map image file
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function checkFileExists($printErr) {
        global $CORE;
        return $CORE->checkExisting($this->path, $printErr);
    }

    /**
     * Checks for readable map image file
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function checkFileReadable($printErr) {
        global $CORE;
        return $CORE->checkReadable($this->path, $printErr);
    }

    /**
     * Checks for writeable map image file
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function checkFileWriteable($printErr) {
        global $CORE;
        return $CORE->checkWriteable($this->path, $printErr);
    }

    /**
     * Checks for writeable map image folder
     *
     * @param	Boolean $printErr
     * @return	Boolean	Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    protected function checkFolderWriteable($printErr) {
        global $CORE;
        return $CORE->checkWriteable(dirname($this->path), $printErr);
    }

    /**
    * Creates a simple map image
    *
    * @param	Boolean	$printErr
    * @return	Boolean	Is Check Successful?
    * @author	Lars Michelsen <lars@vertical-visions.de>
    */
    function createImage($color, $width, $height) {
        $this->path = path('sys', '', 'backgrounds') . '/' . $this->image;
        if(!$this->checkFileExists(0)) {
            if($this->checkFolderWriteable(1)) {
                $image = imagecreatetruecolor($width, $height);

                // get rgb color from hexcode
                $color = str_replace('#','',$color);
                $int = hexdec($color);
                $r = 0xFF & ($int >> 0x10);
                $g = 0xFF & ($int >> 0x8);
                $b = 0xFF & $int;

                $bgColor = imagecolorallocate($image, $r, $g, $b);
                imagefill($image, 0, 0, $bgColor);
                imagepng($image, $this->path);
                imagedestroy($image);

                return TRUE;
            } else {
                // No need for error handling here
                return FALSE;
            }
        } else {
            if($printErr) {
                throw new NagVisException(l('imageAlreadyExists',
                                            Array('IMAGE' => $this->path)));
            }
            return FALSE;
        }
    }

    /**
     * Deletes the map image
     *
     * @param	Boolean	$printErr
     * @return	Boolean	Is Check Successful?
     * @author	Lars Michelsen <lars@vertical-visions.de>
     */
    function deleteImage($printErr=1) {
        if($this->checkFolderWriteable($printErr) && $this->checkFileWriteable($printErr)) {
            if(unlink($this->path)) {
                return TRUE;
            } else {
                if($printErr) {
                    throw new NagVisException(l('couldNotDeleteMapImage',
                                                Array('IMGPATH' => $this->path)));
                }
                return FALSE;
            }
        }
    }
}
?>
