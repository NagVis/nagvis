<?php
/*****************************************************************************
 *
 * GlobalFileCache.php - Class for handling caching of config files etc.
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
class GlobalFileCache
{
    /** @var string[] */
    private $files;

    /** @var string */
    private $cacheFile;

    /** @var false|int */
    private $fileAge;

    /** @var false|int|null */
    private $cacheFileAge = null;

    /**
     * Class Constructor
     *
     * @param string|array $files File to check
     * @param string $cacheFile Path to cache file
     * @throws NagVisException
     * @author    Lars Michelsen <lm@larsmichelsen.com>
     */
    public function __construct($files, $cacheFile)
    {
        if (is_string($files)) {
            $this->files = [$files];
        } else {
            $this->files = $files;
        }

        $this->cacheFile = $cacheFile;

        $this->fileAge = $this->getNewestFileAge();

        if ($this->checkCacheFileExists(0)) {
            $this->cacheFileAge = filemtime($this->cacheFile);
        }
    }

    /**
     * Get the newest of the given files. This is needed to test if
     * the cache file is up-to-date or needs to be renewed
     *
     * @return false|int
     * @throws NagVisException
     */
    private function getNewestFileAge()
    {
        $age = -1;
        $newestFile = '';
        foreach ($this->files as $file) {
            if (
                !GlobalCore::getInstance()->checkExisting($file, false)
                || !GlobalCore::getInstance()->checkReadable($file, false)
            ) {
                continue;
            }

            $thisAge = filemtime($file);
            if ($age === -1) {
                $age = $thisAge;
                $newestFile = $file;
            } elseif ($thisAge > $age) {
                $age = $thisAge;
                $newestFile = $file;
            }
        }

        return $age;
    }

    /**
     * Reads the cached things from cache and returns them
     *
     * @return mixed Cached things
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getCache()
    {
        return unserialize(file_get_contents($this->cacheFile));
    }

    /**
     * Writes the things to cache
     *
     * @param mixed $contents which should be written to cache
     * @param bool $printErr
     * @return bool Is Successful?
     * @throws NagVisException
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function writeCache($contents, $printErr = 1)
    {
        // Perform file writeable check only when cache file exists
        // When no cache file exists check if file can be created in directory
        if (
            (!$this->checkCacheFileExists(0) && $this->checkCacheFolderWriteable($printErr))
            || ($this->checkCacheFileExists(0) && $this->checkCacheFileWriteable($printErr))
        ) {
            if (($fp = fopen($this->cacheFile, 'w+')) === false) {
                if ($printErr == 1) {
                    throw new NagVisException(l('cacheFileNotWriteable', ['FILE' => $this->cacheFile]));
                }
                return false;
            }

            fwrite($fp, serialize($contents));
            fclose($fp);

            GlobalCore::getInstance()->setPerms($this->cacheFile);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the file has been cached
     *
     * @param bool $printErr
     * @return int Unix timestamp of cache creation time or -1 when not cached
     * @throws NagVisException
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function isCached($printErr = false)
    {
        // Checks
        // a) Cache file exists
        // b) Cache file older than regular file
        if (
            $this->checkCacheFileExists($printErr)
            && $this->getFileAge() <= $this->getCacheFileAge()
        ) {
            return $this->getCacheFileAge();
        } else {
            if ($printErr) {
                throw new NagVisException(l('fileNotCached',
                    [
                        'FILE' => json_encode($this->files),
                        'CACHEFILE' => $this->cacheFile
                    ]));
            }
            return -1;
        }
    }

    /**
     * Checks for writeable cache folder
     *
     * @param bool $printErr
     * @return bool  Is Successful?
     * @throws NagVisException
     * @author    Lars Michelsen <lm@larsmichelsen.com>
     */
    private function checkCacheFolderWriteable($printErr)
    {
        return GlobalCore::getInstance()->checkWriteable(dirname($this->cacheFile), $printErr);
    }

    /**
     * Checks for writeable cache file
     *
     * @param bool $printErr
     * @return bool Is Successful?
     * @throws NagVisException
     * @author    Lars Michelsen <lm@larsmichelsen.com>
     */
    private function checkCacheFileWriteable($printErr)
    {
        return GlobalCore::getInstance()->checkWriteable($this->cacheFile, $printErr);
    }

    /**
     * Checks for existing cache file
     *
     * @param bool $printErr
     * @return bool Is Successful?
     * @throws NagVisException
     * @author    Lars Michelsen <lm@larsmichelsen.com>
     */
    private function checkCacheFileExists($printErr)
    {
        return GlobalCore::getInstance()->checkExisting($this->cacheFile, $printErr);
    }

    /**
     * Returns the last modification time of the template file
     *
     * @return int Unix Timestamp
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    private function getFileAge()
    {
        return $this->fileAge;
    }

    /**
     * Returns the last modification time of the cache file
     *
     * @return int Unix Timestamp
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function getCacheFileAge()
    {
        return $this->cacheFileAge;
    }
}
