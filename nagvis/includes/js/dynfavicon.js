// Favicon.js - Change favicon dynamically [http://ajaxify.com/run/favicon].
// Copyright (c) 2006 Michael Mahemoff. Only works in Firefox and Opera.
// Background and MIT License notice at end of file, see the homepage for more.

// USAGE:
// * favicon.change("/icon/active.ico");  (Optional 2nd arg is new title.)
// * favicon.animate(new Array("icon1.ico", "icon2.ico", ...));
//     Tip: Use "" as the last element to make an empty icon between cycles.
//     To stop the animation, call change() and pass in the new arg.
//     (Optional 2nd arg is animation pause in millis, overwrites the default.)
// * favicon.defaultPause = 5000;

var favicon = {

// -- "PUBLIC" ----------------------------------------------------------------

defaultPause: 2000,

change: function(iconURL, optionalDocTitle) {
  clearTimeout(this.loopTimer);
  if (optionalDocTitle) {
    document.title = optionalDocTitle;
  }
  this.addLink(iconURL, true);
},

animate: function(iconSequence, optionalDelay) {
  this.preloadIcons(iconSequence);
  this.iconSequence = iconSequence;
  this.sequencePause = (optionalDelay) ?  optionalDelay : this.defaultPause;
  favicon.index = 0;
  favicon.change(iconSequence[0]);
  this.loopTimer = setInterval(function() {
    favicon.index = (favicon.index+1) % favicon.iconSequence.length;
    favicon.addLink(favicon.iconSequence[favicon.index], false);
  }, favicon.sequencePause);
},

// -- "PRIVATE" ---------------------------------------------------------------

loopTimer: null,

preloadIcons: function(iconSequence) {
  var dummyImageForPreloading = document.createElement("img");
  for (var i=0, len = iconSequence.length; i < len; i++) {
    dummyImageForPreloading.src = iconSequence[i];
  }
},

addLink: function(iconURL) {
  var link = document.createElement("link");
  link.type = "image/x-icon";
  link.rel = "shortcut icon";
  link.href = iconURL;
  this.removeLinkIfExists();
  this.docHead.appendChild(link);
  link = null;
},

removeLinkIfExists: function() {
  var links = this.docHead.getElementsByTagName("link");
  for (var i=0, len = links.length; i<len; i++) {
    var link = links[i];
    if (link.type=="image/x-icon" && link.rel=="shortcut icon") {
      this.docHead.removeChild(link);
      return; // Assuming only one match at most.
    }
  }
},

docHead:document.getElementsByTagName("head")[0]
}

// BACKGROUND
// The main point of this script is to give you a means of alerting the user
// something has happened while your application is in a background tab. Serves
// a similar task to notifications in the operating system taskbar. A secondary
// function is to support favicon animation.
//
// This script works by DOM manipulation. After a call, there will be exactly one
// "rel='icon'" link and one "rel='shortcut icon'" link under the head element.
// Both of these are required for portability reasons. It would be nice  (from
// a performance perspective) if we could just update an existing link, if it
// already exists, but it turns out we can't. Firefox (and others?) will ignore
// changes to the link's attributes; it's only interested in a new link being
// added. So we have to delete and re-add in all cases.

// LEGAL
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights 
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
// copies of the Software, and to permit persons to whom the Software is 
// furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE.
