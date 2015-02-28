<?php
/*****************************************************************************
 *
 * GlobalFileCache.php - Class for handling caching of config files etc.
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
class GlobalFileCache {
    private $files;
    private $cacheFile;

    private $fileAge;
    private $cacheFileAge = null;

    /**
     * Class Constructor
     *
     * @param 	String  File to check
     * @param   String  Path to cache file
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct($files, $cacheFile) {
        if(is_string($files)) {
            $this->files = Array($files);
        } else {
            $this->files = $files;
        }

        $this->cacheFile = $cacheFile;

        $this->fileAge = $this->getNewestFileAge();

        if($this->checkCacheFileExists(0)) {
            $this->cacheFileAge = filemtime($this->cacheFile);
        }
    }

    /**
     * Get the newest of the given files. This is needed to test if
     * the cache file is up-to-date or needs to be renewed
     */
    private function getNewestFileAge() {
        $age = -1;
        $newestFile = '';
        foreach($this->files AS $file) {
            if(!GlobalCore::getInstance()->checkExisting($file, false)
               || !GlobalCore::getInstance()->checkReadable($file, false))
                continue;

            $thisAge = filemtime($file);
            if($age === -1) {
                $age = $thisAge;
                $newestFile = $file;
            } elseif($thisAge > $age) {
                $age = $thisAge;
                $newestFile = $file;
            }
        }

        return $age;
    }

    /**
     * Reads the cached things from cache and returns them
     *
     * @return	Cached things
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getCache() {
        return unserialize(file_get_contents($this->cacheFile));
    }

    /**
     * Writes the things to cache
     *
     * @param   Things which should be written to cache
     * @param   Boolean $printErr
     * @return  Boolean	Is Successful?
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function writeCache($contents, $printErr=1) {
        // Perform file writeable check only when cache file exists
        // When no cache file exists check if file can be created in directory
        if((!$this->checkCacheFileExists(0)
            && $this->checkCacheFolderWriteable($printErr))
           || ($this->checkCacheFileExists(0) && $this->checkCacheFileWriteable($printErr))) {
            if(($fp = fopen($this->cacheFile, 'w+')) === FALSE){
                if($printErr == 1) {
                    throw new NagVisException(l('cacheFileNotWriteable', Array('FILE' => $this->cacheFile)));
                }
                return FALSE;
            }

            fwrite($fp, serialize($contents));
            fclose($fp);

            GlobalCore::getInstance()->setPerms($this->cacheFile);

            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Checks if the file has been cached
     *
     * @param   Boolean  $printErr
     * @return  Integer  Unix timestamp of cache creation time or -1 when not cached
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function isCached($printErr = false) {
        // Checks
        // a) Cache file exists
        // b) Cache file older than regular file
        if($this->checkCacheFileExists($printErr)
            && $this->getFileAge() <= $this->getCacheFileAge()) {
            return $this->getCacheFileAge();
        } else {
            if($printErr) {
                throw new NagVisException(l('fileNotCached',
                                          Array('FILE' => $this->file,
                                                'CACHEFILE' => $this->cacheFile)));
            }
            return -1;
        }
    }

    /**
     * Checks for writeable cache folder
     *
     * @param   Boolean  $printErr
     * @return  Boolean  Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkCacheFolderWriteable($printErr) {
        return GlobalCore::getInstance()->checkWriteable(dirname($this->cacheFile), $printErr);
    }

    /**
     * Checks for writeable cache file
     *
     * @param   Boolean  $printErr
     * @return  Boolean  Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkCacheFileWriteable($printErr) {
        return GlobalCore::getInstance()->checkWriteable($this->cacheFile, $printErr);
    }

    /**
     * Checks for existing cache file
     *
     * @param   Boolean  $printErr
     * @return  Boolean  Is Successful?
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function checkCacheFileExists($printErr) {
        return GlobalCore::getInstance()->checkExisting($this->cacheFile, $printErr);
    }

    /**
     * Returns the last modification time of the template file
     *
     * @return  Integer  Unix Timestamp
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function getFileAge() {
        return $this->fileAge;
    }

    /**
     * Returns the last modification time of the cache file
     *
     * @return  Integer  Unix Timestamp
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function getCacheFileAge() {
        return $this->cacheFileAge;
    }
}
?>
