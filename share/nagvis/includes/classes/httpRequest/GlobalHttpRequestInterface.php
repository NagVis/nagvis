<?php
/*****************************************************************************
 *
 * GlobalRequest.php - Interface for GlobalHttpRequest
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: michael_luebben@web.de)
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
 * class GlobalRequestInterface
 *
 * Interface class  GlobalHttpRequest
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
interface GlobalHttpRequestInterface {

	/**
	 * Get parameter names from http request
	 *
	 * @return  array Returns an array with parameter names
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameterNames();

	/**
	 * Check if isset a parameter
	 *
	 * @param   string Name from parameter which has to be checked
	 * @return  string Return null or the parameter name
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function issetParameter($name);

	/**
	 * Get value from parameter
	 *
	 * @param   string name Parametername
	 * @return  string Returns value from parameter
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameter($name);

	/**
	 * Get value from header
	 *
	 * @param string name Name for header parameter
	 *        Examples:
	 *           HOST
	 *           USER_AGENT
	 *           ACCEPT
	 *           ACCEPT_LANGUAGE
	 *           etc.
	 *
	 * @return  Returns value from header
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getHeader($name);
} // end of GlobalRequest
?>
