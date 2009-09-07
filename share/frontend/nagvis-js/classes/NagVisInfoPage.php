<?php
/*****************************************************************************
 *
 * NagVisInfoPage - Display information about nagvis, apache and php installation
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
 * class NagVisInfoPage
 *
 * @author  Michael Luebben <michael_luebben@web.de>
 */
class NagVisInfoPage {
	private $CORE;
	private $infoPage;

	/**
	 * Constructor
	 *
	 * @param   Object  $CORE
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __construct($CORE) {
		$this->CORE = $CORE;
	}

	/**
	 * Build nagvis pag with information
	 *
	 * @access  public
	 * @author  Michael Luebben <michael_luebben@web.de>
	 */
	public function __toString() {
		$infoPage  = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">'."\n";
		$infoPage .= '<html>'."\n";
		$infoPage .= '   <head>'."\n";
		$infoPage .= '      <title>'.$this->CORE->MAINCFG->getValue('internal', 'title').'</title>'."\n";
		$infoPage .= '      <style type="text/css"><!-- @import url('.$this->CORE->MAINCFG->getValue('paths','htmlbase').'/nagvis/includes/css/style.css);  --></style>'."\n";
		$infoPage .= '   </head>'."\n";
		$infoPage .= '   <body class="main">'."\n";
		$infoPage .= '      <div class="infopage">'."\n";
		$infoPage .= '         <table class="instinfo">'."\n";
		$infoPage .= '            <tr><th colspan="2" class="head">Support Informations</td></tr>'."\n";
		$infoPage .= '         </table><br />'."\n";
		$infoPage .= '         <table class="instinfo">'."\n";
		$infoPage .= '            <tr><th colspan="2">Version Informations</td></tr>'."\n";
		$infoPage .= '            <tr><td>NagVis Version</td><td>'.CONST_VERSION.'</td></tr>'."\n";
		$infoPage .= '            <tr><td>PHP Version</td><td>'.PHP_VERSION.'</td></tr>'."\n";
		$infoPage .= '            <tr><td>MySQL Version</td><td>'.shell_exec('mysql --version').'</td></tr>'."\n";
		$infoPage .= '            <tr><td>OS</td><td>'.shell_exec('uname -a').'</td></tr>'."\n";
		$infoPage .= '            <t><th colspan="2">Webserver Informations</th></tr>'."\n";
		$infoPage .= '            <tr><td>SERVER_SOFTWARE</td><td>'.$_SERVER['SERVER_SOFTWARE'].'</td></tr>'."\n";
		$infoPage .= '            <tr><td>REMOTE_USER</td><td>'.$_SERVER['REMOTE_USER'].'</td></tr>'."\n";
		$infoPage .= '            <tr><td>SCRIPT_FILENAME</td><td>'.$_SERVER['SCRIPT_FILENAME'].'</td></tr>'."\n";
		$infoPage .= '            <tr><td>SCRIPT_NAME</td><td>'.$_SERVER['SCRIPT_NAME'].'</td></tr>'."\n";
		$infoPage .= '            <tr><td>REQUEST_TIME</td><td>'.$_SERVER['REQUEST_TIME'].' (gmdate(): '.gmdate('r',$_SERVER['REQUEST_TIME']).')</td></tr>'."\n";
		$infoPage .= '            <t><th colspan="2">PHP Informations</th></tr>'."\n";
		$infoPage .= '            <tr><td>error_reporting</td><td>'.ini_get('error_reporting').'</td></tr>'."\n";
		$infoPage .= '            <tr><td>safe_mode</td><td>'.(ini_get('safe_mode')?"yes":"no").'</td></tr>'."\n";
		$infoPage .= '            <tr><td>max_execution_time</td><td>'.ini_get('max_execution_time').' seconds</td></tr>'."\n";
		$infoPage .= '            <tr><td>memory_limit</td><td>'.ini_get('memory_limit').'</td></tr>'."\n";
		$infoPage .= '            <tr><td>loaded modules</td><td>'.implode(", ",get_loaded_extensions()).'</td></tr>'."\n";
		$infoPage .= '            <t><th colspan="2">Client Informations</th></tr>'."\n";
		$infoPage .= '            <tr><td>USER_AGENT</td><td>'.$_SERVER['HTTP_USER_AGENT'].'</td></tr>'."\n";
		$infoPage .= '         </table>'."\n";
		$infoPage .= '      </div>'."\n";
		$infoPage .= '   </body>'."\n";;
		$infoPage .= '</html>'."\n";;

		return $infoPage;
	}
}
?>