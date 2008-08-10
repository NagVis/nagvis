/*****************************************************************************
 *
 * nagvis.js - Some NagVis function which are used in NagVis frontend
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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

/**
 * Function to start the page refresh/rotation
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function refresh() {
	if(rotate) {
		if(nextRotationUrl != '') {
			window.open(nextRotationUrl, "_self");
		} else {
			window.location.reload(true);
		}
	} else {
		var tmp = String(window.location).replace(/#/g, '')
		/**
		 * When rotation is active we have to set the rotate option to 0
		 * so the map is even not rotated on next reload
		 */
		if(getUrlParam('rotation') != '') {
			if(!tmp.match("rotate=0")) {
				if(tmp.search(/\?/) != -1) {
					tmp = tmp+"&rotate=0";
				} else {
					tmp = tmp+"?rotate=0";
				}
			}
		}
		window.open(tmp, "_self");
	}
}

/**
 * Function counts down in 1 second intervals. If nextRotationTime is smaller
 * than 0, refresh/rotate
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function countdown() {
	nextRefreshTime -= 1;
	if(nextRefreshTime <= 0) {
		refresh();
	} else {
		// Only countdown if the refresh countdown is enabled
		if(bRefresh) {
			// write the time to refresh to header counter
			if(document.getElementById('refreshCounterHead')) {
				document.getElementById('refreshCounterHead').innerHTML = nextRefreshTime;
			}
			// write the time to refresh to the normal counter
			if(document.getElementById('refreshCounter')) {
							document.getElementById('refreshCounter').innerHTML = nextRefreshTime;
					}
			// 1 second timeout to next countdown call
			window.setTimeout('countdown()', 1000);
		}
	}
}

/**
 * Function to start/stop the refresh countdown
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function switchRefresh(obj,startLabel,stopLabel) {
	if(bRefresh) {
		bRefresh=false;
		setRefreshLabel(startLabel,stopLabel);
	} else {
		bRefresh=true;
		countdown();
		setRefreshLabel(startLabel,stopLabel);
	}
}

/**
 * Function to set the refresh switch label dynamicaly
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setRefreshLabel(startLabel,stopLabel) {
	if(bRefresh) {
		document.getElementById('refreshSwitch').innerHTML = stopLabel;
	} else {
		document.getElementById('refreshSwitch').innerHTML = startLabel;
	}
}

/**
 * Function to start/stop the rotation
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function switchRotation(obj,startLabel,stopLabel) {
	if(rotate) {
		rotate=false;
		setRotationLabel(startLabel,stopLabel);
	} else {
		rotate=true;
		setRotationLabel(startLabel,stopLabel);
	}
}

/**
 * Function to set the rotation switch label dynamicaly
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function setRotationLabel(startLabel,stopLabel) {
	if(getUrlParam('rotation') == '') {
		document.getElementById('rotationSwitch').style.visibility = 'hidden';
	} else {
		if(rotate) {
			document.getElementById('rotationSwitch').innerHTML = stopLabel;
		} else {
			document.getElementById('rotationSwitch').innerHTML = startLabel;
		}
	}
}

/**
 * Function gets the value of url params
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function getUrlParam(name) {
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec(window.location);
	if( results == null ) {
		return '';
	} else {
		return results[1];
	}
}

function changeMap(htmlBase,mapName) {
	if(mapName.match('^__automap')) {
		location.href=htmlBase+'/nagvis/index.php?automap=1'+mapName.replace('__automap','');
	} else {
		if (mapName == "") {
			location.href=htmlBase+'/nagvis/index.php';
		} else {
			location.href=htmlBase+'/nagvis/index.php?map='+mapName;
		}
	}
}
