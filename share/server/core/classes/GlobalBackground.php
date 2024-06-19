<?php
/*****************************************************************************
 *
 * GlobalBackground.php - Class for global background image handling
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

/**
 * @author	Lars Michelsen <lm@larsmichelsen.com>
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
     * @return	string File Name
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    private function getFileName() {
        return $this->image;
    }

    /**
     * Gets the locationtype of the file
     *
     * @return	string File Name
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getFileType() {
        return $this->type;
    }

    /**
     * Fetches the path and saves it on initial load
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
     * @param   bool $bWebPath Get web path or alternatively the physical path
     * @return  string  HTML Path to background file
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getFile($bWebPath = true) {
        if($bWebPath) {
            return $this->webPath;
        } else {
            return $this->path;
        }
    }

    /**
     * Checks for existing map image file
     *
     * @param	bool $printErr
     * @return	bool	Is Successful?
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    protected function checkFileExists($printErr) {
        global $CORE;
        return $CORE->checkExisting($this->path, $printErr);
    }

    /**
     * Checks for readable map image file
     *
     * @param	bool $printErr
     * @return	bool	Is Successful?
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    protected function checkFileReadable($printErr) {
        global $CORE;
        return $CORE->checkReadable($this->path, $printErr);
    }

    /**
     * Checks for writeable map image file
     *
     * @param	bool $printErr
     * @return	bool	Is Successful?
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    protected function checkFileWriteable($printErr) {
        global $CORE;
        return $CORE->checkWriteable($this->path, $printErr);
    }

    /**
     * Checks for writeable map image folder
     *
     * @param	bool $printErr
     * @return	bool	Is Successful?
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    protected function checkFolderWriteable($printErr) {
        global $CORE;
        return $CORE->checkWriteable(dirname($this->path), $printErr);
    }

    /**
     * Deletes the map image
     *
     * @param	bool	$printErr
     * @return	bool	Is Check Successful?
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function deleteImage($printErr = 1) {
        if($this->checkFolderWriteable($printErr) && $this->checkFileWriteable($printErr)) {
            if(unlink($this->path)) {
                return true;
            } else {
                if($printErr) {
                    throw new NagVisException(l('couldNotDeleteMapImage',
                                                ['IMGPATH' => $this->path]));
                }
                return false;
            }
        }
        return false;
    }
}

