<?php
/*******************************************************************************
 *
 * CoreModUrl.php - Core module to handle ajax requests for urls
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
 * @author Lars Michelsen <lm@larsmichelsen.com>
 */
class CoreModUrl extends CoreModule {
    private $url = null;

    public function __construct(GlobalCore $CORE) {
        $this->sName = 'Url';
        $this->CORE = $CORE;

        $aOpts = Array('show' => MATCH_STRING_URL);
        $aVals = $this->getCustomOptions($aOpts);
        $this->url = $aVals['show'];

        // Register valid actions
        $this->aActions = Array(
            'getContents'   => 'view',
        );
    }

    public function handleAction() {
        $sReturn = '';

        if($this->offersAction($this->sAction)) {
            switch($this->sAction) {
                case 'getContents':
                    $sReturn = $this->getContents();
                break;
            }
        }

        return $sReturn;
    }

    private function getContents() {
        $content = '';

        // Suppress error messages from file_get_contents
        $oldLevel = error_reporting(0);

        // Only allow urls not paths for security reasons
        // Reported here: http://news.gmane.org/find-root.php?message_id=%3cf60c42280909021938s7f36c0edhd66d3e9156a5d081%40mail.gmail.com%3e
        $url = parse_url($this->url);
        if(!isset($url['scheme']) || $url['scheme'] == '') {
            throw new NagVisException(l('problemReadingUrl', Array(
                'URL' => htmlentities($this->url, ENT_COMPAT, 'UTF-8'),
                'MSG' => 'Not allowed url')));
            exit(1);
        }

        if (false == ($content = file_get_contents($this->url))) {
            $error = error_get_last();
            throw new NagVisException(l('problemReadingUrl', Array(
                'URL' => htmlentities($this->url, ENT_COMPAT, 'UTF-8'),
                'MSG' => $error['message'])));
        }

        // set the old level of reporting back
        error_reporting($oldLevel);

        return json_encode(Array('content' => $content));
    }
}
?>
