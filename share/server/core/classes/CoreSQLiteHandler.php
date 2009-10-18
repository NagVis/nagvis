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
	private $CORE = null;
	private $DB = null;
	
	public function __construct() {
		
	}
	
	public function open($file) {
		$this->DB = new SQLiteDatabase($file, 0664, $sError);
		
		if($this->DB === false) {
			return false;
		} else {
			return true;
		}
	}
	
	public function tableExist($table) {
	  $RES = $this->DB->query('SELECT COUNT(*) FROM sqlite_master WHERE type=\'table\' AND name=\''.$table.'\'');
	  return intval($RES->fetchSingle()) > 0;
	}
	
	public function query($query) {
		return $this->DB->query($query);
	}
	
	public function fetchAssoc($RES) {
		return $RES->fetch(SQLITE_ASSOC);
	}
	
	public function close() {
		$this->DB->close();
	}
}
?>
