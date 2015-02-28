/*****************************************************************************
 *
 * dynfavicon.js - functions for handling dynamical changes of the page
 *                 favicon image. This code is based on Favicon.js from
 *                 Michael Mahemoff. For details take a look at the bottom of
 *                 this file.
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
 *****************************************************************************/


var favicon = {
    // -- "PUBLIC" ----------------------------------------------------------------

    change: function(iconURL) {
        this.addLink(iconURL, true);
    },

    // -- "PRIVATE" ---------------------------------------------------------------

    addLink: function(iconURL) {
        var docHead = document.getElementsByTagName("head")[0];

        var link = document.createElement("link");
        link.type = "image/x-icon";
        link.rel = "shortcut icon";
        link.href = iconURL;

        this.removeLinkIfExists();
        docHead.appendChild(link);

        // Cleanup
        link = null;
        docHead = null;
    },

    removeLinkIfExists: function() {
        var docHead = document.getElementsByTagName("head")[0];
        var links = docHead.getElementsByTagName("link");

        if(!docHead || !links)
            return false;

        for (var i=0, len = links.length; i<len; i++) {
            if (links[i] && links[i].type == "image/x-icon" && links[i].rel == "shortcut icon") {
                docHead.removeChild(links[i]);
            }
        }

        // Cleanup
        links = null;
        docHead = null;

        return true;
    }
};

// Favicon.js - Change favicon dynamically [http://ajaxify.com/run/favicon].
// Copyright (c) 2006 Michael Mahemoff. Only works in Firefox and Opera.
// Background and MIT License notice at end of file, see the homepage for more.

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