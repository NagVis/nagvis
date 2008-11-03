<?php
/*****************************************************************************
 *
 * GlobalValidator.php - Validator for parameters
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
 * class GlobalValidator
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class GlobalValidator {

	// Name from parameter which has to be checked
	private $parameterName = NULL;

	// Value for parameter
	private $parameterValue = NULL;

	// This array contains parameter with options which be allowed
	private $validatorArr = array();

	// This variable contains message for validator
	private $message = NULL;

	/**
	 * Constructor
	 *
	 * @param   $string name   Name from parameter
	 * @param           value  Value from parameter
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __construct($name, $value) {
		$this->parameterName = $name;
		$this->parameterValue = $value;

		/**
		 * Array with allowed parameter and their options
		 *
		 * Global options for all parameter types:
		 *		name:				string	Name from parameter
		 *		type:				string	Type from parameter. Allowed types are string, integer,
		 *										boolean and url
		 *		mustSet			boolean	Defines whether a variable must to be set (TRUE or FALSE)
		 *		notEmpty			boolean	Defines whether a variable can be empty (TRUE or FALSE)
		 *
		 * Options only for parameter type string:
		 * 	allowedEntrys	array		Contains names which are only allowed for this parameter
		 *
		 * Options only for parameter type integer:
		 *		mustInRange		boolean	Defines whether an integer must to in a range (TRUE or FALSE)
		 *		minValue			integer	Minimal value (mustInRange must be TRUE)
		 *		maxValue			integer	Maximal value (mustInRange must be TRUE)
		 */
		$this->validatorArr = array(
			// Define action
			'action' => array(
				'name'         => 'action',
				'type'         => 'string',
				'mustSet'      => TRUE,
				'notEmpty'		=> FALSE,
				'allowedEntrys'=> array(
					'0' => 'map',
					'1' => 'automap',
					'2' => 'rotation',
					'3' => 'info',
					'4' => 'url'
				)
			),
			// Define map
			'map' => array(
				'name'         => 'map',
				'type'         => 'string',
				'mustSet'      => TRUE,
				'notEmpty'		=> TRUE,
				'allowedEntrys'=> NULL
			),
			// Define automap
			'automap' => array(
				'name'         => 'automap',
				'type'         => 'boolean',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
			),
			// Define backend
			'backend' => array(
				'name'         => 'backend',
				'type'         => 'string',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
				'allowedEntrys'=> NULL
			),
			// Define root
			'root' => array(
				'name'         => 'root',
				'type'         => 'string',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
				'allowedEntrys'=> NULL
			),
			// Define maxLayers
			'maxLayers' => array(
				'name'         => 'maxLayers',
				'type'         => 'integer',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
				'mustInRange'  => TRUE,
				'minValue'     => '0',
				'maxValue'     => '9999'
			),
			// Define renderMode
			'renderMode' => array(
				'name'         => 'renderMode',
				'type'         => 'string',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
				'allowedEntrys'=> array(
					'0' => 'directed',
					'1' => 'undirected',
					'2' => 'radial',
					'3' => 'circular',
					'4' => 'undirected2'
				)
			),
			// Define width
			'width' => array(
				'name'         => 'width',
				'type'         => 'integer',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
				'mustInRange'  => TRUE,
				'minValue'     => '0',
				'maxValue'     => '9999'
			),
			// Define height
			'height' => array(
				'name'         => 'height',
				'type'         => 'integer',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
				'mustInRange'  => TRUE,
				'minValue'     => '0',
				'maxValue'     => '9999'
			),
			// Define ignoreHosts
			'ignoreHosts' => array(
				'name'         => 'ignoreHosts',
				'type'         => 'string',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
				'allowedEntrys'=> NULL
			),
			// Define filterGroup
			'filterGroup' => array(
				'name'         => 'filterGroup',
				'type'         => 'string',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
				'allowedEntrys'=> NULL
			),
			// Define rotation
			'rotation' => array(
				'name'         => 'rotation',
				'type'         => 'string',
				'mustSet'      => TRUE,
				'notEmpty'		=> FALSE,
				'allowedEntrys'=> NULL
			),
			// Define info
			'info' => array(
				'name'         => 'info',
				'type'         => 'string',
				'mustSet'      => FALSE,
				'notEmpty'		=> FALSE,
				'allowedEntrys'=> NULL
			),
			// Define url
			'url' => array(
				'name'         => 'url',
				'type'         => 'url',
				'mustSet'      => TRUE,
				'notEmpty'		=> TRUE,
				'allowedEntrys'=> NULL
			)
		);
	}

	/**
	 * Check if have parameterName a valid value
	 *
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function isValid() {
		// Check if parameter is allowed
		if (FALSE === $this->isValidParameterName()) {
			return FALSE;
		} else {
			switch ($this->validatorArr[$this->parameterName]['type']) {
				case 'integer':
					$parameter = new GlobalValidatorInteger($this->validatorArr[$this->parameterName], $this->parameterValue);
					break;

				case 'string':
					$parameter = new GlobalValidatorString($this->validatorArr[$this->parameterName], $this->parameterValue);
					break;

				case 'boolean':
					$parameter = new GlobalValidatorBoolean($this->validatorArr[$this->parameterName], $this->parameterValue);
					break;

				case 'url':
					$parameter = new GlobalValidatorUrl($this->validatorArr[$this->parameterName], $this->parameterValue);
					break;
			}
		}

		if (TRUE === $parameter->isvalid()) {
			return TRUE;
		} else {
			$this->message = $parameter->getMessage();
			return FALSE;
		}
	}

	/**
	 * Return message
	 *
	 * @return  string   Message from object
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Return name from parameter
	 *
	 * @return  string   Name from parameter
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameterName() {
		return $this->parameterName;
	}

	/**
	 * Return value from parameter
	 *
	 * @return  string   Value from parameter
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function getParameterValue() {
		return $this->parameterValue;
	}

	/**
	 * Check if parameterName a defined parameter
	 *
	 * @access  private
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function isValidParameterName() {
		foreach ($this->validatorArr as $validatorName => $validatorObject) {
			if ($validatorName == $this->parameterName) {
				return TRUE;
			}
		}

		$this->setMessage('validatorNotDefinedParameter');
		return FALSE;
	}

	/**
	 * Return message from validator
	 *
	 * @param   string   $tring
	 * @access  private
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	private function setMessage($string) {
		$this->message = $string;
	}
}
?>