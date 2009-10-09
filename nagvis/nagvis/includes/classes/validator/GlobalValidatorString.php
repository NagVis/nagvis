<?php
/*****************************************************************************
 *
 * GlobalValidatorString.php - Class to check if parameter a valid string.
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
 * class GlobalValidatorString
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalValidatorString extends GlobalValidatorAbstract {

	// Contains array with options to check the parameter
	private $validateArr;

	// Contains value from parameter
	private $parameterValue;

	/**
	 * Constructor
	 *
	 * @param   array    $validateArr
	 * @param   integer  $parameterValue
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __construct($validateArr, $parameterValue) {
		$this->validateArr = $validateArr;
		$this->parameterValue = $parameterValue;
	}

	/**
	 * Set private variables
	 *
	 * @param   string   $name    Name from variable
	 * @param            $value   Value for variable
	 * @access  private
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	private function __set($name, $value) {
		$this->name = $value;
	}

	/**
	 * Check if has parameter a valid string
	 *
	 * @return  boolean
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function isValidParameter() {
		// Check if parameter is set
		if (TRUE === $this->validateArr['mustSet']) {
			if (FALSE === $this->mustSet($this->parameterValue)) {
				return FALSE;
			}
		}

		// Check if parameter is empty
		if (TRUE === $this->validateArr['notEmpty']) {
			if (FALSE === $this->notEmpty($this->parameterValue)) {
				return FALSE;
			}
		}

		// Check if value is an allowed entry
		if (NULL !== $this->validateArr['allowedEntrys']) {
			if (FALSE === $this->isAllowedEntry()) {
				return FALSE;
			}
		}

		// Check if parameter value is a string
		if (FALSE === $this->isString()) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Check if value is a valid string
	 *
	 * @return  boolean
	 * @access  protected
	 * @author  Michael Luebben <michael_luebben@web.de>
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	protected function isString() {
		if(preg_match('/^[a-zA-Z0-9_\-,\.\s]*$/',$this->parameterValue)) {
			return TRUE;
		} else {
			$this->setMessage('validatorNotValidString');
			return FALSE;
		}
	}

	/**
	 * Check if value is an allowed entry
	 *
	 * @return  boolean
	 * @access  protected
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	protected function isAllowedEntry() {
		foreach ($this->validateArr['allowedEntrys'] as $allowedEntry) {
			if ($allowedEntry == $this->parameterValue) {
				return TRUE;
			}
		}

		$this->setMessage('ValidatorNotAllowedParameterEntry');
		return FALSE;
	}
}
?>
