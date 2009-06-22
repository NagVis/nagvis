<?php
/*****************************************************************************
 *
 * GlobalHttpRequest.php - Handles http request
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
 * class GlobalHttpRequest
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalHttpRequest implements GlobalHttpRequestInterface {

	private $parameters;


	/**
	 * Copy value from $_REQUEST to parameters
	 *
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __construct( ) {
		$this->parameters = $_REQUEST;
		
		// The session ID parameter is not relevant
		unset($this->parameters['SESSID']);
	} // end of member function __construktor

	/**
	 * Get parameter names from http request
	 *
	 * @return  array Returns an array with parameter names
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameterNames() {
		return array_keys($this->parameters);
	} // end of member function getParameterName

	/**
	 * Check if isset a parameter
	 *
	 * @param   string Name from parameter which has to be checked
	 * @return  string Return null or the parameter name
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function issetParameter($name ) {
		if (isset($this->parameters[$name])) {
			return $this->parameters[$name];
		}
		return NULL;
	} // end of member function issetParam

	/**
	 * Get value from parameter
	 *
	 * @param   string name Parametername
	 * @return  string Returns value from parameter
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameter( $name) {
		return $this->parameters[$name];
	} // end of member function getParameter

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
	public function getHeader($name) {
		$name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
		if (isset($_SERVER[$name])) {
			return $_SERVER[$name];
		}
		return NULL;
	} // end of member function getHeader
} // end of GlobalHttpRequest
?>