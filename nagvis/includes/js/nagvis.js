/**
 * Function to start the page refresh/rotation
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function refresh() {
	if(rotate) {
		window.open(nextRotationUrl, "_self");
	} else {
		var tmp = String(window.location).replace(/#/g, '')
		if(!tmp.match("rotate=0")) {
			tmp = tmp+"&rotate=0"
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
	nextRotationTime -= 1;
	if(nextRotationTime <= 0) {
		refresh();
	} else {
		// write the time to refresh somewhere
		if(document.getElementById('refreshCounter')) {
			document.getElementById('refreshCounter').innerHTML = nextRotationTime;
		}
		// 1 second timeout to next countdown call
		window.setTimeout('countdown()', 1000);
	}
}

/**
 * Function to stop the refresh/rotation
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
 * Function to set the switch label dynamicaly
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
	var results = regex.exec(window.location.href);
	if( results == null ) {
		return '';
	} else {
		return results[1];
	}
}
		