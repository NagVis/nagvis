/**
 * Function to start the page refresh/rotation
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
 * Function to stop the refresh/rotation
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
 */
function setRotationLabel(startLabel,stopLabel) {
	if(rotate) {
		document.getElementById('rotationSwitch').innerHTML = stopLabel;
	} else {
		document.getElementById('rotationSwitch').innerHTML = startLabel;
	}
}

		