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

// Define some state options
var oStates = Object();
oStates.UNREACHABLE = Object();
oStates.UNREACHABLE.color = '#FF0000';
oStates.UNREACHABLE.sound = '';
oStates.DOWN = Object();
oStates.DOWN.color = '#FF0000';
oStates.DOWN.sound = '';
oStates.CRITICAL = Object();
oStates.CRITICAL.color = '#FF0000';
oStates.CRITICAL.sound = '';
oStates.WARNING = Object();
oStates.WARNING.color = '#FFFF00';
oStates.WARNING.sound = '';
oStates.UNKNOWN = Object();
oStates.UNKNOWN.color = '#FFCC66';
oStates.UNKNOWN.sound = '';
oStates.ERROR = Object();
oStates.ERROR.color = '#0000FF';
oStates.ERROR.sound = '';
oStates.UP = Object();
oStates.UP.color = '#00FF00';
oStates.UP.sound = '';
oStates.OK = Object();
oStates.OK.color = '#00FF00';
oStates.OK.sound = '';
oStates.PENDING = Object();
oStates.PENDING.color = '#C0C0C0';
oStates.PENDING.sound = '';

/**
 * Update the worker counter
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function updateWorkerCounter() {
	// write the time to refresh to header counter
	if(document.getElementById('workerLastRunCounter')) {
		document.getElementById('workerLastRunCounter').innerHTML = date(oGeneralProperties.date_format, oWorkerProperties.last_run/1000);
	}
	return true;
}

/**
 * Function to start the page refresh/rotation
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function rotatePage() {
	if(oPageProperties.nextStepUrl != '') {
		if(oPageProperties.rotationEnabled) {
			window.open(oPageProperties.nextStepUrl, "_self");
			return true;
		}
	} else {
		window.location.reload(true);
		return true;
	}
	return false;
}

/**
 * Function counts down in 1 second intervals. If nextRotationTime is smaller
 * than 0, refresh/rotate
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function rotationCountdown() {
	if(oPageProperties.nextStepTime != '') {
		// Countdown one second
		oPageProperties.nextStepTime -= 1;
		
		if(oPageProperties.nextStepTime <= 0) {
			return rotatePage();
		} else {
			// write the time to refresh to header counter
			if(document.getElementById('refreshCounterHead')) {
				document.getElementById('refreshCounterHead').innerHTML = oPageProperties.nextStepTime;
			}
			// write the time to refresh to the normal counter
			if(document.getElementById('refreshCounter')) {
				document.getElementById('refreshCounter').innerHTML = oPageProperties.nextStepTime;
			}
		}
	}
	return false;
}

/**
 * Function to start/stop the rotation
 *
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function switchRotation(obj, startLabel, stopLabel) {
	if(oPageProperties.rotationEnabled) {
		oPageProperties.rotationEnabled = false;
		setRotationLabel(startLabel, stopLabel);
	} else {
		oPageProperties.rotationEnabled = true;
		setRotationLabel(startLabel, stopLabel);
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
		if(oPageProperties.rotationEnabled) {
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

function getCurrentTime() {
	var oDate = new Date();
	var sHours = oDate.getHours();
	sHours = (( sHours < 10) ? "0"+sHours : sHours);
	var sMinutes = oDate.getMinutes();
	sMinutes = (( sMinutes < 10) ? "0"+sMinutes : sMinutes);
	var sSeconds = oDate.getSeconds();
	sSeconds = (( sSeconds < 10) ? "0"+sSeconds : sSeconds);
	
	return sHours+":"+sMinutes+":"+sSeconds;
}

function getRandomLowerCaseLetter() {
   return String.fromCharCode(97 + Math.round(Math.random() * 25));
}

function getRandom(min, max) {
	if( min > max ) {
		return -1;
	}
	
	if( min == max ) {
		return min;
	}
	
	return min + parseInt(Math.random() * (max-min+1));
}

function cloneObject(what) {
	var o;
	
	if(what instanceof Array) {
		o = new Array();
	} else {
		o = new Object();
	}
	
	for (i in what) {
		if (typeof what[i] == 'object') {
			if(i != 'parsedObject') {
				o[i] = cloneObject(what[i]);
			}
		} else {
			o[i] = what[i];
		}
	}
	
	return o;
}

function date(format, timestamp) {
	// http://kevin.vanzonneveld.net
	// +   original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
	// +      parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   improved by: MeEtc (http://yass.meetcweb.com)
	// +   improved by: Brad Touesnard
	// +   improved by: Tim Wiel
	// *     example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400);
	// *     returns 1: '09:09:40 m is month'
	// *     example 2: date('F j, Y, g:i a', 1062462400);
	// *     returns 2: 'September 2, 2003, 2:26 am'
	
	var a, jsdate=((timestamp) ? new Date(timestamp*1000) : new Date());
	var pad = function(n, c){
		if( (n = n + "").length < c ) {
			return new Array(++c - n.length).join("0") + n;
		} else {
			return n;
		}
	};
	var txt_weekdays = ["Sunday","Monday","Tuesday","Wednesday",
		"Thursday","Friday","Saturday"];
	var txt_ordin = {1:"st",2:"nd",3:"rd",21:"st",22:"nd",23:"rd",31:"st"};
	var txt_months =  ["", "January", "February", "March", "April",
		"May", "June", "July", "August", "September", "October", "November",
		"December"];
	
	var f = {
		// Day
		d: function(){
				return pad(f.j(), 2);
		},
		D: function(){
				t = f.l(); return t.substr(0,3);
		},
		j: function(){
				return jsdate.getDate();
		},
		l: function(){
				return txt_weekdays[f.w()];
		},
		N: function(){
				return f.w() + 1;
		},
		S: function(){
				return txt_ordin[f.j()] ? txt_ordin[f.j()] : 'th';
		},
		w: function(){
				return jsdate.getDay();
		},
		z: function(){
				return (jsdate - new Date(jsdate.getFullYear() + "/1/1")) / 864e5 >> 0;
		},
		// Week
		W: function(){
				var a = f.z(), b = 364 + f.L() - a;
				var nd2, nd = (new Date(jsdate.getFullYear() + "/1/1").getDay() || 7) - 1;
	
				if(b <= 2 && ((jsdate.getDay() || 7) - 1) <= 2 - b){
						return 1;
				} else{
	
						if(a <= 2 && nd >= 4 && a >= (6 - nd)){
								nd2 = new Date(jsdate.getFullYear() - 1 + "/12/31");
								return date("W", Math.round(nd2.getTime()/1000));
						} else{
								return (1 + (nd <= 3 ? ((a + nd) / 7) : (a - (7 - nd)) / 7) >> 0);
						}
				}
		},
		// Month
		F: function(){
				return txt_months[f.n()];
		},
		m: function(){
				return pad(f.n(), 2);
		},
		M: function(){
				t = f.F(); return t.substr(0,3);
		},
		n: function(){
				return jsdate.getMonth() + 1;
		},
		t: function(){
				var n;
				if( (n = jsdate.getMonth() + 1) == 2 ){
						return 28 + f.L();
				} else{
						if( n & 1 && n < 8 || !(n & 1) && n > 7 ){
								return 31;
						} else{
								return 30;
						}
				}
		},
		
		// Year
		L: function(){
				var y = f.Y();
				return (!(y & 3) && (y % 1e2 || !(y % 4e2))) ? 1 : 0;
		},
		//o not supported yet
		Y: function(){
				return jsdate.getFullYear();
		},
		y: function(){
				return (jsdate.getFullYear() + "").slice(2);
		},
		// Time
		a: function(){
				return jsdate.getHours() > 11 ? "pm" : "am";
		},
		A: function(){
				return f.a().toUpperCase();
		},
		B: function(){
				// peter paul koch:
				var off = (jsdate.getTimezoneOffset() + 60)*60;
				var theSeconds = (jsdate.getHours() * 3600) +
												 (jsdate.getMinutes() * 60) +
													jsdate.getSeconds() + off;
				var beat = Math.floor(theSeconds/86.4);
				if (beat > 1000) beat -= 1000;
				if (beat < 0) beat += 1000;
				if ((String(beat)).length == 1) beat = "00"+beat;
				if ((String(beat)).length == 2) beat = "0"+beat;
				return beat;
		},
		g: function(){
				return jsdate.getHours() % 12 || 12;
		},
		G: function(){
				return jsdate.getHours();
		},
		h: function(){
				return pad(f.g(), 2);
		},
		H: function(){
				return pad(jsdate.getHours(), 2);
		},
		i: function(){
				return pad(jsdate.getMinutes(), 2);
		},
		s: function(){
				return pad(jsdate.getSeconds(), 2);
		},
		//u not supported yet
		// Timezone
		//e not supported yet
		//I not supported yet
		O: function(){
			 var t = pad(Math.abs(jsdate.getTimezoneOffset()/60*100), 4);
			 if (jsdate.getTimezoneOffset() > 0) t = "-" + t; else t = "+" + t;
			 return t;
		},
		P: function(){
				var O = f.O();
				return (O.substr(0, 3) + ":" + O.substr(3, 2));
		},
		//T not supported yet
		//Z not supported yet
		// Full Date/Time
		c: function(){
				return f.Y() + "-" + f.m() + "-" + f.d() + "T" + f.h() + ":" + f.i() + ":" + f.s() + f.P();
		},
		//r not supported yet
		U: function(){
				return Math.round(jsdate.getTime()/1000);
		}
	};
	
	return format.replace(/[\\]?([a-zA-Z])/g, function(t, s){
			if( t!=s ){
					// escaped
					ret = s;
			} else if( f[s] ){
					// a date function exists
					ret = f[s]();
			} else{
					// nothing special
					ret = s;
			}
			
			return ret;
		});
}

function scrollSlow(iTargetX, iTargetY, iSpeed) {
	var currentScrollTop;
	var currentScrollLeft;
	
	var iStep = 2;
	
	if (typeof window.pageYOffset != 'undefined') {
		currentScrollTop = window.pageYOffset;
	} else if (typeof document.compatMode != 'undefined' && document.compatMode != 'BackCompat') {
		currentScrollTop = document.documentElement.scrollTop;
	} else if (typeof document.body != 'undefined') {
		currentScrollTop = document.body.scrollTop;
	}
	
	if (typeof window.pageXOffset != 'undefined') {
		currentScrollLeft = window.pageXOffset;
	} else if (typeof document.compatMode != 'undefined' && document.compatMode != 'BackCompat') {
		currentScrollLeft = document.documentElement.scrollLeft;
	} else if (typeof document.body != 'undefined') {
		currentScrollLeft = document.body.scrollLeft;
	}
	
	// FIXME: Check if the difference is smaller than iStep. When it is, lower iStep
	// to make the right step size
	
	if(currentScrollTop > iTargetY) {
		scrollTop = -iStep;
	} else if(currentScrollTop < iTargetY) {
		scrollTop = iStep;
	} else {
		scrollTop = 0;
	}
	
	if(currentScrollLeft > iTargetX) {
		scrollLeft = -iStep;
	} else if(currentScrollLeft < iTargetX) {
		scrollLeft = iStep;
	} else {
		scrollLeft = 0;
	}
	
	
	eventlog("scroll", "info", currentScrollLeft+" to "+iTargetX+" = "+scrollLeft+", "+currentScrollTop+" to "+iTargetY+" = "+scrollTop);
	
	if(scrollTop != 0 || scrollLeft != 0) {
		window.scrollBy(scrollLeft, scrollTop);
		
		setTimeout('scrollSlow('+iTargetX+', '+iTargetY+', '+iSpeed+')', iSpeed);
	} else {
		alert('No need to scroll: '+currentScrollLeft+' - '+iTargetX+', '+currentScrollTop+' - '+iTargetY);
	}
}
