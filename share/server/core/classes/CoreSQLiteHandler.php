<?php
/*******************************************************************************
 *
 * CoreSQLiteHandler.php - Class to handle SQLite databases
 *
 * Copyright (c) 2004-2009 NagVis Project (Contact: info@nagvis.org)
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
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreSQLiteHandler {
	private $DB = null;
	
	public function __construct() {}
	
	public function open($file) {
		// First check if the php installation supports sqlite
		if($this->checkSQLiteSupport()) {
			try {
				$this->DB = new PDO("sqlite:".$file);
			} catch(PDOException $e) {
    		echo $e->getMessage();
    		return false;
    	}
			
			if($this->DB === false || $this->DB === null) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	
	public function tableExist($table) {
	  $RET = $this->query('SELECT COUNT(*) AS num FROM sqlite_master WHERE type=\'table\' AND name='.$this->escape($table))->fetch(PDO::FETCH_ASSOC);
	  return intval($RET['num']) > 0;
	}
	
	public function query($query) {
		return $this->DB->query($query);
	}
	
	public function exec($query) {
		return $this->DB->exec($query);
	}
	
	public function count($query) {
		$RET = $this->query($query)->fetch(PDO::FETCH_ASSOC);
	  return intval($RET['num']) > 0;
	}
	
	public function fetchAssoc($RES) {
		return $RES->fetch(PDO::FETCH_ASSOC);
	}
	
	public function close() {
		$this->DB = null;
	}
	
	public function escape($s) {
		return $this->DB->quote($s);
	}
	
	private function checkSQLiteSupport($printErr = 1) {
		if(!class_exists('PDO')) {
			if($printErr === 1) {
				new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Your PHP installation does not support PDO. Please check if you installed the PHP module.'));
			}
			return false;
		} elseif(!in_array('sqlite', PDO::getAvailableDrivers())) {
			if($printErr === 1) {
				new GlobalMessage('ERROR', GlobalCore::getInstance()->getLang()->getText('Your PHP installation does not support PDO SQLite (3.x). Please check if you installed the PHP module.'));
			}
			return false;
		} else {
			return true;
		}
	}
}
?>
