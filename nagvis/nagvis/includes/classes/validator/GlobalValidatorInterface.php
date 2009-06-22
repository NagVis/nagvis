<?php
/*****************************************************************************
 *
 * GlobalValidatorInterface.php - Interface for GlobalValidator
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
 * class interface GlobalValidator
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
interface GlobalValidatorInterface {

	/**
	 * Check if parameter is valid
	 *
	 * @return
	 * @access  protected
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function isValid();

	/**
	 * Get message
	 *
	 * @return
	 * @access  protected
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getMessage();

	/**
	 * Get name from parameter
	 *
	 * @return  string   Name from parameter
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameterName();

	/**
	 * Get value from parameter
	 *
	 * @return  string   Value from parameter
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameterValue();
}
?>