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
class CoreModUrl extends CoreModule
{
    /** @var GlobalCore */
    private $CORE;

    /** @var string|null */
    private $url = null;

    /**
     * @param GlobalCore $CORE
     */
    public function __construct(GlobalCore $CORE)
    {
        $this->sName = 'Url';
        $this->CORE = $CORE;

        $aOpts = ['show' => MATCH_STRING_URL];
        $aVals = $this->getCustomOptions($aOpts);
        $this->url = $aVals['show'];

        // Register valid actions
        $this->aActions = [
            'getContents'   => 'view',
        ];
    }

    /**
     * @return false|string
     * @throws NagVisException
     */
    public function handleAction()
    {
        $sReturn = '';

        if ($this->offersAction($this->sAction)) {
            if ($this->sAction == 'getContents') {
                $sReturn = $this->getContents();
            }
        }

        return $sReturn;
    }

    /**
     * @return false|string
     * @throws NagVisException
     */
    private function getContents()
    {
        $content = '';

        // Suppress error messages from file_get_contents
        $oldLevel = error_reporting(0);

        // Only allow urls not paths for security reasons
        // Reported here: http://news.gmane.org/find-root.php?message_id=%3cf60c42280909021938s7f36c0edhd66d3e9156a5d081%40mail.gmail.com%3e
        $url = parse_url($this->url);
        if (!isset($url['scheme']) || ($url['scheme'] != 'http' && $url['scheme'] != 'https')) {
            throw new NagVisException(
                l(
                    'problemReadingUrl',
                    [
                        'URL' => htmlentities($this->url, ENT_COMPAT, 'UTF-8'),
                        'MSG' => 'Not allowed url'
                    ]
                )
            );
        }

        // Only accept known URLs. The only place where NagVis defines URLs is in rotation steps.
        // Get all configured URLs from all configured rotations and check whether or not it's
        // an allowed URL.
        if (!$this->isAllowedUrl()) {
            throw new NagVisException(l('problemReadingUrl', [
                'URL' => htmlentities($this->url, ENT_COMPAT, 'UTF-8'),
                'MSG' => 'Not allowed url'
            ]));
        }

        if (!($content = file_get_contents($this->url))) {
            $error = error_get_last();
            throw new NagVisException(l('problemReadingUrl', [
                'URL' => htmlentities($this->url, ENT_COMPAT, 'UTF-8'),
                'MSG' => $error['message']
            ]));
        }

        // set the old level of reporting back
        error_reporting($oldLevel);

        return json_encode(['content' => $content]);
    }

    /**
     * @return bool
     * @throws NagVisException
     */
    private function isAllowedUrl()
    {
        global $CORE;
        $allowed = [];

        foreach ($CORE->getPermittedRotationPools() as $pool_name) {
            $ROTATION = new CoreRotation($pool_name);

            $iNum = $ROTATION->getNumSteps();
            for ($i = 0; $i < $iNum; $i++) {
                $step = $ROTATION->getStepById($i);
                if (isset($step['url']) && $step['url'] != '') {
                    $allowed[$step['url']] = true;
                }
            }
        }

        return isset($allowed[$this->url]);
    }
}
