/*****************************************************************************
 *
 * NagVisRotation.js - This class handles the visualisation of the rotations
 *
 * Copyright (c) 2004-2008 NagVis Project
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
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */

NagVisRotation.Inherits(NagVisStatelessObject);
function NagVisRotation (oConf) {
	// Call parent constructor
	this.Inherits(NagVisStatelessObject, oConf);
	
	this.parseOverview = function() {
		var oTable = document.getElementById('overviewRotations');
		
		/* Rotation title */
		
		var oTr = document.createElement('tr');
		var oTd = document.createElement('td');
		oTd.setAttribute('rowSpan', this.conf.num_steps);
		oTd.rowSpan = this.conf.num_steps;
		
		var url = this.conf.url;
		
		oTd.onclick = function() { 
			location.href = url;
		};
		
		oTd.onmouseover = function() {
			this.style.cursor = 'pointer';
			this.style.backgroundColor = '#ffffff';
		};
		
		oTd.onmouseout = function() {
			this.style.cursor = 'auto';
			this.style.backgroundColor = '';
		};
		
		var oH2 = document.createElement('h2');
		oH2.appendChild(document.createTextNode(this.conf.name));
		oTd.appendChild(oH2);
		
		oTr.appendChild(oTd);
		
		/* Rotation steps */
		
		for(var i = 0, len = this.conf.steps.length; i < len; i++) {
			if(i !== 0) {
				oTr = document.createElement('tr');
				
				// Dummy cell to get rowspan work
				oTd = document.createElement('td');
				oTr.appendChild(oTd);
			}
			
			oTd = document.createElement('td');
			oTd.width = '250px';
			
			var sUrl = this.conf.steps[i].url;
			
			oTd.onclick = function() { 
				location.href = sUrl;
			};
			
			oTd.onmouseover = function() {
				this.style.cursor = 'pointer';
				this.style.backgroundColor = '#ffffff';
			};
			
			oTd.onmouseout = function() {
				this.style.cursor = 'auto';
				this.style.backgroundColor = '';
				return nd();
			};
			
			oTd.appendChild(document.createTextNode(this.conf.steps[i].name));
			
			oTr.appendChild(oTd);
			oTable.appendChild(oTr);
		}
	}
	
	/*$aRotations[] = Array('name' => $poolName,
					                      'url' => $ROTATION->getNextStepUrl(),
					                      'num_steps' => $ROTATION->getNumSteps(),
														    'steps' => $aSteps);*/
	
	/*// Form the onClick action
	$onClick = 'location.href=\''.$ROTATION->getNextStepUrl().'\';';
	
	// Form the HTML code for the rotation cell
	$ret .= '<tr>';
	$ret .= '<td rowspan="'.$ROTATION->getNumSteps().'" onMouseOut="this.style.cursor=\'auto\';this.bgColor=\'\';return nd();" onMouseOver="this.style.cursor=\'pointer\';this.bgColor=\'#ffffff\';" onClick="'.$onClick.'">';
	$ret .= '<h2>'.$poolName.'</h2><br />';
	$ret .= '</td>';
	
	// Parse the code for the step list
	foreach($ROTATION->getSteps() AS $intId => $arrStep) {
		if($intId != 0) {
			$ret .= '<tr>';
		}
		$onClick = 'location.href=\''.$ROTATION->getStepUrlById($intId).'\';';
		$ret .= '<td width="250" onMouseOut="this.style.cursor=\'auto\';this.bgColor=\'\';return nd();" onMouseOver="this.style.cursor=\'pointer\';this.bgColor=\'#ffffff\';" onClick="'.$onClick.'">';
		$ret .= $ROTATION->getStepLabelById($intId).'</td>';
		$ret .= '</tr>';
	}*/
}
