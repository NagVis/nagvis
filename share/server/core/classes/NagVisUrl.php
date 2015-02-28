<?php
/*****************************************************************************
 *
 * NagVisUrl.php - This class handles urls which should be shown in NagVis
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
class NagVisUrl {
    private $CORE;

    private $strUrl;
    private $strContents;

    /**
     * Class Constructor
     *
     * @param   GlobalCore 	$CORE
     * @param   String      URL
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct(GlobalCore $CORE, $strUrl) {
        $this->CORE = $CORE;

        $this->strUrl = $strUrl;
        $this->strContents = '';
    }

    /**
     * Fetches the contets of the specified URL
     *
     * @param 	GlobalCore 	$CORE
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    private function fetchContents() {
        // Suppress error messages from file_get_contents
        $oldLevel = error_reporting(0);

        // Only allow urls not paths for security reasons
        // Reported here: http://news.gmane.org/find-root.php?message_id=%3cf60c42280909021938s7f36c0edhd66d3e9156a5d081%40mail.gmail.com%3e
        $aUrl = parse_url($this->strUrl);
        if(!isset($aUrl['scheme']) || $aUrl['scheme'] == '') {
            throw new NagVisException(l('problemReadingUrl', Array('URL' => htmlentities($this->strUrl, ENT_COMPAT, 'UTF-8'),
                                                                   'MSG' => 'Not allowed url')));
            exit(1);
        }

        if(false == ($this->strContents = file_get_contents($this->strUrl))) {
            $aError = error_get_last();

            throw new NagVisException(l('problemReadingUrl', Array('URL' => htmlentities($this->strUrl, ENT_COMPAT, 'UTF-8'),
                                                                   'MSG' => $aError['message'])));
        }

        // set the old level of reporting back
        error_reporting($oldLevel);
    }

    /**
     * Gets the contents of the URL
     *
     * @return  String  Contents of the URL
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function getContents() {
        if($this->strContents == '') {
            $this->fetchContents();
        }

        return $this->strContents;
    }

    /**
     * Parses the url options in json format
     *
     * @return	String 	String with JSON Code
     * @author 	Lars Michelsen <lars@vertical-visions.de>
     */
    public function parsePropertiesJson() {
        return json_encode(Array('url' => $this->strUrl));
    }
}
?>
