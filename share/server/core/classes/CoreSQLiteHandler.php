<?php

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
