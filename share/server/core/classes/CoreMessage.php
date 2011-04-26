<?php
/*****************************************************************************
 *
 * CoreMessage.php - Class to render a message in the core
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
 * class CoreMessage
 *
 * @param   string   $type
 *			Follow type available:
 *				Message type for services:
 *					- ok		 		-> Green box for ok messages
 *					- warning 		-> Yellow box for warning messages
 *					- unknown 		-> orange box for unknown messages
 *					- critical	 	-> Red box for error messages
 *
 *				Message type for hosts:
 *					- up		 		-> Green box for up messages
 *					- down	 		-> Red box for error messages
 *					- unknown 		-> orange box for unknown messages
 *					- unreachable 	-> orange box for unknown messages
 *
 *				Message type for another messages:
 *					- note 	 		-> Blue box for Information
 *					- error	 		-> Red box for error messages
 *					- permission	-> Orange box for permission messages
 *
 * @param   string   $title	Title for the Box
 * @param   string   $message	Message
 *
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
class CoreMessage {
    // Contains path to the html base
    private $pathHtmlBase;

    //This variables contains information which will be used to build the message box
    private $type;
    private $message;
    private $title;
    private $reloadTime = null;
    private $reloadUrl = null;

    /**
     * The contructor checks the type and builds the message box
     *
     * @param   strind	$type
     * @param   string	$message
     * @param   string	$pathHtmlBase
     * @param   string	$title
     * @access  public
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function __construct($type, $message, $pathHtmlBase, $title) {
        $this->type = $type;
        $this->message = $message;
        $this->pathHtmlBase = $pathHtmlBase;
        $this->title = $title;
    }

    /**
     * Sets the redirect/reload information for the message box
     *
     * @access  public
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function reload($time, $url) {
        $this->reloadTime = $time;
        $this->reloadUrl = $url;
    }

    /**
     * Build a message for the user for ajax frontend
     *
     * @access  private
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    private function buildMessage() {
        $aMessage = Array('type' => $this->type,
                          'message' => $this->message,
                          'title' => $this->title,
                          'reloadTime' => $this->reloadTime,
                          'reloadUrl' => $this->reloadUrl);
        return 'NagVisError:'.json_encode($aMessage);
    }

    /**
     * Print the message box
     *
     * return   String  HTML Code
     * @access  private
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function __toString () {
        return $this->buildMessage();
    }
}
?>