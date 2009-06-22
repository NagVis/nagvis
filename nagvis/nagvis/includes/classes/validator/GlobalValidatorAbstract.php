<?php
/*****************************************************************************
 *
 * GlobalValidatorAbstract.php - Abstract class GlobalValidatorInteger, GlobalValidatorString etc...
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
 * class GlobalValidatorAbstract
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalValidatorAbstract {

	// Contains message from this validator
	private $message = NULL;

	/**
	 * Check if parameter is valid
	 *
	 * @return  boolean
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function isValid() {
		return $this->isValidParameter();
	}

	/**
	 * Get a message
	 *
	 * @return  string   Message from object
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Set a message
	 *
	 * @param   string   $tring
	 * @access  private
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	protected function setMessage($string) {
		$this->message = $string;
	}

	/**
	 * Check if parameter is set
	 *
	 * @return  boolean
	 * @access  protected
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	protected function mustSet($value) {
		if (isset($value)) {
			return TRUE;
		}

		$this->setMessage('validatorNotSetParameterValue');
		return FALSE;
	}

	/**
	 * Check if parameter is empty
	 *
	 * @return  boolean
	 * @access  protected
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	protected function notEmpty($value) {
		if ($value != '') {
			return TRUE;
		}

		$this->setMessage('validatorIsEmptyParameter');
		return FALSE;
	}

}
?>