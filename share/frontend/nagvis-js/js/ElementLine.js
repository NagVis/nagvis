/*****************************************************************************
 *
 * ElementLine.js - This class handles the visualisation of lines
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
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

var ElementLineControls = Element.extend({
    render: function() {
        // don't render the controls during normal render calls. We
        // want to keep the number of DOM objects low, so only render
        // them when being unlocked for the first time. But when the
        // element has already been rendered, then re-render it!
        if (this.dom_obj)
            this._render();
    },

    draw: function() {
        // When locked: Don't draw on regular draw calls (only draw when locked/unlocked)
        if (!this.obj.bIsLocked)
            this.base();
    },

    unlock: function() {
        if (!this.dom_obj)
            this._render();
        this._draw();
    },

    lock: function() {
        this.erase();
    },

    place: function() {
        // FIXME: This should be possible without re-rendering everything
        if (!this.obj.bIsLocked) {
            this.erase();
            this._render();
            this.draw();
        }
    },

    //
    // END OF PUBLIC METHODS
    //

    _draw: function() {
        Element.prototype.draw.call(this);
    },

    _render: function() {
        var container = document.createElement('div');
        container.setAttribute('id', this.obj.conf.object_id+'-controls');
        this.dom_obj = container;

        var x = this.obj.parseCoords(this.obj.conf.x, 'x');
        var y = this.obj.parseCoords(this.obj.conf.y, 'y');
        var size = 10;

        for (var i = 0, l = x.length; i < l; i++)
            this.renderDragger(i, x[i], y[i], - size / 2, - size / 2, size);

        if (this.hasTwoParts())
            this.renderMidToggle(x.length+2,
                this.obj.getLineMid(this.obj.conf.x, 'x'),
                this.obj.getLineMid(this.obj.conf.y, 'y'),
                20 - size / 2,
                -size / 2 + 5,
                size);
    },

    hasTwoParts: function() {
        return this.obj.conf.view_type === 'line'
               && (this.obj.conf.line_type == 10
                   || this.obj.conf.line_type == 13
                   || this.obj.conf.line_type == 14
                   || this.obj.conf.line_type == 15);
    },

    renderDragger: function (num, objX, objY, offX, offY, size) {
        var ctl = document.createElement('div');
        this.dom_obj.appendChild(ctl);
        ctl.setAttribute('id', this.obj.conf.object_id+'-drag-' + num);
        ctl.className = 'control drag';
        // FIXME: Multilanguage
        ctl.title          = 'Move object';
        ctl.style.zIndex   = parseInt(this.obj.conf.z)+1;
        ctl.style.width    = addZoomFactor(size) + 'px';
        ctl.style.height   = addZoomFactor(size) + 'px';
        ctl.style.left     = (objX + offX) + 'px';
        ctl.style.top      = (objY + offY) + 'px';
        ctl.objOffsetX     = offX;
        ctl.objOffsetY     = offY;

        var img = document.createElement('img');
        img.src = '../../frontend/nagvis-js/images/internal/control_drag.png';
        img.style.width    = addZoomFactor(size) + 'px';
        img.style.height   = addZoomFactor(size) + 'px';
        ctl.appendChild(img);

        makeDragable(ctl, this.obj, this.obj.saveObject, this.obj.moveObject);
    },

    // Adds the modify button to the controls including all eventhandlers
    renderMidToggle: function (num, objX, objY, offX, offY, size) {
        var ctl = document.createElement('div');
        this.dom_obj.appendChild(ctl);
        ctl.setAttribute('id', this.obj.conf.object_id+'-togglemid-' + num);
        ctl.className = 'control togglemid';
	// FIXME: Multilanguage
        if (this.obj.bIsLocked)
	    ctl.title = 'Unlock line middle';
        else
	    ctl.title = 'Lock line middle';
        ctl.style.zIndex   = parseInt(this.obj.conf.z)+1;
        ctl.style.width    = addZoomFactor(size) + 'px';
        ctl.style.height   = addZoomFactor(size) + 'px';
        ctl.style.left     = (objX + offX) + 'px';
        ctl.style.top      = (objY + offY) + 'px';
        ctl.objOffsetX     = offX;
        ctl.objOffsetY     = offY;

        var img = document.createElement('img');
        if (this.isMidLocked())
            img.src = '../../frontend/nagvis-js/images/internal/control_locked.png';
        else
            img.src = '../../frontend/nagvis-js/images/internal/control_unlocked.png';
        img.style.width    = addZoomFactor(size) + 'px';
        img.style.height   = addZoomFactor(size) + 'px';
        ctl.appendChild(img);

        addEvent(ctl, 'click', function(element_obj) {
            return function(event) {
                event = event || window.event;
                element_obj.toggleMidLock();
	        contextHide();
                return preventDefaultEvents(event);
            };
        }(this));
        ctl = null;
    },

    isMidLocked: function() {
        return this.obj.conf.x.split(',').length == 2;
    },

    /**
     * Toggles the position of the line middle. The mid of the line
     * can either be the 2nd of three line coords or is automaticaly
     * the middle between two line coords.
     */
    toggleMidLock: function() {
        // What is the current state?
        var x = this.obj.conf.x.split(',');
        var y = this.obj.conf.y.split(',')

        if (this.isMidLocked()) {
            // The line has 2 coords configured
            // - Calculate and add the 3rd coord as 2nd
            // - Add a drag control for the 2nd coord
            this.obj.conf.x = [
              x[0],
              middle(this.obj.parseCoords(this.obj.conf.x, 'x', false)[0], this.obj.parseCoords(this.obj.conf.x, 'x', false)[1], this.obj.conf.line_cut),
              x[1],
            ].join(',');
            this.obj.conf.y = [
                y[0],
                middle(this.obj.parseCoords(this.obj.conf.y, 'y', false)[0], this.obj.parseCoords(this.obj.conf.y, 'y', false)[1], this.obj.conf.line_cut),
                y[1],
            ].join(',');
        } else {
            // The line has 3 coords configured
            // - Remove the 2nd coord
            // - Remove the drag control for the 2nd coord
            this.obj.conf.x = [ x[0], x[2] ].join(',');
            this.obj.conf.y = [ y[0], y[2] ].join(',');
        }

        var parts = g_view.unproject(this.obj.conf.x.toString().split(','),
                                     this.obj.conf.y.toString().split(','));
        var x = parts[0].join(',');
        var y = parts[1].join(',');

        // send to server
        saveObjectAttr(this.obj.conf.object_id, {'x': x, 'y': y});

        // redraw the whole object
        this.obj.render();
    }
});

var ElementLine = Element.extend({
    line_container : null,
    parts          : null,
    canvas         : null,
    perfdata       : null,
    // The link area is some kind of a hack. The canvas detects whether or not
    // the mouse moves over the line. In the momennt the mouse moves over the
    // line, the link_area div node is moved at the mouse position to give the
    // user all the controls related to the line which is currently hovered.
    link_area      : null,

    constructor: function(obj) {
        this.parts = [];
        this.base(obj);
    },

    update: function() {
        new ElementLineControls(this.obj).addTo(this.obj);
    },

    updateAttrs: function(only_state) {
        if (!only_state || (this.isWeathermapLine() &&
             (this.obj.stateChanged() || this.obj.outputOrPerfdataChanged()))) {
            this.redrawLine();
        }
    },

    render: function() {
        if (this.isWeathermapLine())
            this.parsePerfdata();

        var container = document.createElement('div');
        container.setAttribute('id', this.obj.conf.object_id+'-linediv');
        container.className = 'line';
        this.dom_obj = container;

        // Create line div
        var oLineDiv = document.createElement('div');
        this.line_container = oLineDiv;
        container.appendChild(oLineDiv);
        oLineDiv.setAttribute('id', this.obj.conf.object_id+'-line');
        // the objects canvas might hide icons behind it, put it one layer down,
        // because normally icons and lines are on the same z-index
        oLineDiv.style.zIndex = parseInt(this.obj.conf.z)-1;

        this.calcLineParts();
        this.renderLine();
        this.renderActionContainer();
        this.renderLinkArea();
        this.renderLabels();
    },

    place: function() {
        this.redrawLine();
    },

    unlock: function() {
        this.toggleActionContainer();
    },

    lock: function() {
        this.toggleActionContainer();
    },

    //
    // END OF PUBLIC METHODS
    //

    redrawLine: function() {
        // Totally redraw the line when moving the line anchors arround. But keep the trigger
        // object because it saves the attached event handlers
        var trigger_obj = this.obj.trigger_obj;
        trigger_obj.parentNode.removeChild(trigger_obj);
        this.clearActionContainer();

        this.erase();
        this.obj.trigger_obj = trigger_obj;
        this.render();
        this.draw();
    },

    renderActionContainer: function() {
        // This is only the container for the hover/label elements. The real area or labels
        // are added later. But this container gets all event handlers assigned. Because
        // the line is using erase(), render(), draw() within place() which is called while
        // the user moves the object, this dom node must not be re-created, because this
        // would remove all event handlers 
        if (!this.obj.trigger_obj) {
            var oLink = document.createElement('a');
            oLink.setAttribute('id', this.obj.conf.object_id+'-linelink');
            oLink.className = 'linelink';
            this.obj.trigger_obj = oLink;
        } else {
            var oLink = this.obj.trigger_obj;
            this.clearActionContainer();
        }
        this.dom_obj.appendChild(oLink);
        if (this.obj.conf.url) {
            oLink.href = this.obj.conf.url;
            oLink.target = this.obj.conf.url_target;
        } else {
            oLink.href = 'javascript:void(0)';
        }
    },

    clearActionContainer: function() {
        while (this.obj.trigger_obj.firstChild)
            this.obj.trigger_obj.removeChild(this.obj.trigger_obj.firstChild);
    },

    toggleActionContainer: function() {
        // Hide if not needed, show if needed
        if (!this.obj.needsLineHoverArea())
            this.obj.trigger_obj.style.display = 'none';
        else
            this.obj.trigger_obj.style.display = 'block';
    },

    calcLineParts: function() {
        this.parts = [];

        var x = this.obj.parseCoords(this.obj.conf.x, 'x');
        var y = this.obj.parseCoords(this.obj.conf.y, 'y');

        // Convert all coords to int
        for (var i = 0; i < x.length; i++) {
            x[i] = parseInt(x[i], 10);
            y[i] = parseInt(y[i], 10);
        }

        var xStart = x[0];
        var yStart = y[0];
        var xEnd   = x[x.length - 1];
        var yEnd   = y[y.length - 1];

        var width = addZoomFactor(this.obj.conf.line_width);
        if (width <= 0)
            width = 1; // minimal width for lines

        // Lines meeting point position
        var cut = this.obj.conf.line_cut;

        switch (this.obj.conf.line_type) {
            case '11': // ---> lines
                this.renderArrow(0, xStart, yStart, xEnd, yEnd, width);
            break;
            case '12': // --- lines
                this.renderSimpleLine(0, xStart, yStart, xEnd, yEnd, width);
            break;
            case '10':
            case '13':
            case '14':
            case '15':
                // two part lines
                if (x.length == 2) {
                    var xMid = middle(xStart, xEnd, cut);
                    var yMid = middle(yStart, yEnd, cut);
                } else {
                    var xMid = x[1];
                    var yMid = y[1];
                }

                this.renderArrow(0, xStart, yStart, xMid, yMid, width);
                this.renderArrow(1, xEnd, yEnd, xMid, yMid, width);
            break;
            default:
                alert('Error: Unknown line type');
        }
    },

    // Calculates the colors of a line part
    calcColors: function(id) {
        var color = '#FFCC66';
        var border_color = '#000000';

        // Get the fill color depending on the object state
        switch (this.obj.conf.summary_state) {
            case 'UNREACHABLE':
            case 'DOWN':
            case 'CRITICAL':
            case 'WARNING':
            case 'UNKNOWN':
            case 'ERROR':
            case 'UP':
            case 'OK':
            case 'PENDING':
                color = oStates[this.obj.conf.summary_state].color;
            break;
        }

        // Adjust fill color based on perfdata for weathermap lines
        if (this.isWeathermapLine())
            color = this.calcWeathermapColor(id);

        // ack/downtime/staleness lighten the color a bit
        if (this.obj.conf.summary_problem_has_been_acknowledged === 1
            || this.obj.conf.summary_in_downtime === 1
            || this.obj.conf.summary_stale) {
            color = lightenColor(color, 100, 100, 100);
        }

        return [color, border_color];
    },

    isWeathermapLine: function() {
        return this.obj.conf.line_type == 13 || this.obj.conf.line_type == 14
               || this.obj.conf.line_type == 15;
    },

    renderLine: function() {
        var allX = [], allY = [];
        for (var i = 0, len = this.parts.length; i < len; i++) {
            allX = allX.concat(this.parts[i][2]);
            allY = allY.concat(this.parts[i][3]);
        }
        var xMin = min(allX);
        var yMin = min(allY);
        var xMax = max(allX);
        var yMax = max(allY);
        var border = 5;

        var canvas = document.createElement('canvas');
        this.canvas = canvas;

        if (!canvas.getContext)
            return; // FIXME: Show an error message?

        canvas.setAttribute('id', this.obj.conf.object_id+'-canvas');
        canvas.style.position = 'absolute';
        this.line_container.appendChild(canvas);

        canvas.style.left = (xMin-border)+"px";
        canvas.style.top = (yMin-border)+"px";
        canvas.width = Math.round(xMax-xMin)+2*border;
        canvas.height = Math.round(yMax-yMin)+2*border;
        // the objects canvas might hide icons behind it, put it one layer down,
        // because normally icons and lines are on the same z-index
        canvas.style.zIndex = parseInt(this.obj.conf.z)-1;

        addEvent(canvas, 'mousemove', this.handleMouseMove.bind(this));

        var ctx = canvas.getContext('2d');
        if (!ctx)
            return; // silently skip

        // On high resolution devices like e.g. 4k screens where
        // the page is not rendered 1:1 but instead shown scaled,
        // the renderd lines look bad if they had been rendered 1:1.
        // Fix this by scaling the whole canvas.
        var devicePixelRatio = window.devicePixelRatio || 1;
        var backingStoreRatio = ctx.webkitBackingStorePixelRatio ||
                                ctx.mozBackingStorePixelRatio ||
                                ctx.msBackingStorePixelRatio ||
                                ctx.oBackingStorePixelRatio ||
                                ctx.backingStorePixelRatio || 1;
        var ratio = devicePixelRatio / backingStoreRatio;
        if (devicePixelRatio !== backingStoreRatio) {
            var oldWidth = canvas.width;
            var oldHeight = canvas.height;

            canvas.width = oldWidth * ratio;
            canvas.height = oldHeight * ratio;

            canvas.style.width = oldWidth + 'px';
            canvas.style.height = oldHeight + 'px';

            // now scale the context to counter
            // the fact that we've manually scaled
            // our canvas element
            ctx.scale(ratio, ratio);
        }

        // Now start rendering by first clearing the canvas.
        // Then start painting the single line parts
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        var part;
        for (var i = 0, len = this.parts.length; i < len; i++) {
            part = this.parts[i];

            ctx.fillStyle = part[4][0];

            ctx.beginPath();
            ctx.moveTo(part[2][0]-xMin+border, part[3][0]-yMin+border);

            for (var a = 1, len2 = part[2].length; a < len2; a++)
                ctx.lineTo(part[2][a]-xMin+border, part[3][a]-yMin+border);

            ctx.fill();

            // border
            ctx.lineWidth = 1;
            ctx.strokeStyle = part[4][1];
            ctx.stroke();
        }
    },

    // This function renders an arrow like it is used on NagVis maps
    // It renders following line types: --->
    renderArrow: function(id, x1, y1, x2, y2, w) {
        var xCoord = [
            x1 + newX(x2-x1, y2-y1, 0, w),
            x2 + newX(x2-x1, y2-y1, -4*w, w),
            x2 + newX(x2-x1, y2-y1, -4*w, 2*w),
            x2,
            x2 + newX(x2-x1, y2-y1, -4*w, -2*w),
            x2 + newX(x2-x1, y2-y1, -4*w, -w),
            x1 + newX(x2-x1, y2-y1, 0, -w),
            x1 + newX(x2-x1, y2-y1, 0, w)
        ];
        var yCoord = [
            y1 + newY(x2-x1, y2-y1, 0, w),
            y2 + newY(x2-x1, y2-y1, -4*w, w),
            y2 + newY(x2-x1, y2-y1, -4*w, 2*w),
            y2,
            y2 + newY(x2-x1, y2-y1, -4*w, -2*w),
            y2 + newY(x2-x1, y2-y1, -4*w, -w),
            y1 + newY(x2-x1, y2-y1, 0, -w),
            y1 + newY(x2-x1, y2-y1, 0, w)
        ];

        this.renderLinePart(id, [x1, y1], [x2, y2], xCoord, yCoord);
    },

    // This function renders simple lines (without arrow)
    renderSimpleLine: function(id, x1, y1, x2, y2, w) {
        var xCoord = [
            x1 + newX(x2-x1, y2-y1, 0, w),
            x2 + newX(x2-x1, y2-y1, w, w),
            x2 + newX(x2-x1, y2-y1, w, -w),
            x1 + newX(x2-x1, y2-y1, 0, -w),
            x1 + newX(x2-x1, y2-y1, 0, w)
        ];
        var yCoord = [
            y1 + newY(x2-x1, y2-y1, 0, w),
            y2 + newY(x2-x1, y2-y1, w, w),
            y2 + newY(x2-x1, y2-y1, w, -w),
            y1 + newY(x2-x1, y2-y1, 0, -w),
            y1 + newY(x2-x1, y2-y1, 0, w)
        ];

        this.renderLinePart(id, [x1, y1], [x2, y2], xCoord, yCoord);
    },

    renderLinePart: function(id, start, end, x, y) {
        this.parts.push([start, end, x, y, this.calcColors(id)]);
    },

    handleMouseMove: function(event) {
        event = event || window.event;

        if (getTargetRaw(event).tagName !== 'CANVAS')
            return true;

        // document.body.scrollTop does not work in IE
        var scrollTop = document.body.scrollTop ? document.body.scrollTop :
                                                  document.documentElement.scrollTop;
        var scrollLeft = document.body.scrollLeft ? document.body.scrollLeft :
                                                    document.documentElement.scrollLeft;

        // Get the mouse position relative to window
        var x = event.clientX - getSidebarWidth() + scrollLeft;
        var y = event.clientY - getHeaderHeight() + scrollTop;

        // Check whether or not the given coord is within the line part coords
        var pnpoly = function(nvert, vertx, verty, testx, testy) {
            var i, j, c = false;
            for ( i = 0, j = nvert-1; i < nvert; j = i++ ) {
                if ( ( ( verty[i] > testy ) != ( verty[j] > testy ) ) &&
                    ( testx < ( vertx[j] - vertx[i] ) * ( testy - verty[i] ) / ( verty[j] - verty[i] ) + vertx[i] ) ) {
                        c = !c;
                }
            }
            return c;
        };

        var part = null,
            over = false;
        for (var i = 0, len = this.parts.length; i < len; i++) {
            part = this.parts[i];
            if (pnpoly(part[2].length, part[2], part[3], x, y)) {
                over = true;
                break;
            }
        }

        if (over) {
            add_class(this.canvas, 'active');
            // move the link area below the cursor to make actions possible
            this.link_area.style.display = '';
            this.link_area.style.left = (x-5) + 'px';
            this.link_area.style.top = (y-5) + 'px';

            if (usesSource('worldmap'))
                this.obj.marker._bringToFront();
        } else {
            remove_class(this.canvas, 'active');
            this.link_area.style.display = 'none';

            if (usesSource('worldmap'))
               this.obj.marker._resetZIndex();
        }
    },

    renderLabels: function() {
        if (!this.isWeathermapLine())
            return; // Only weathermap lines have labels at the moment

        if (!this.obj.conf.line_label_show || this.obj.conf.line_label_show !== '1')
            return; // skip over when labels are disabled

        // First line label position
        // Second line label position
        var cutIn  = this.obj.conf.line_label_pos_in;
        var cutOut = this.obj.conf.line_label_pos_out;
        var yOffset = parseInt(this.obj.conf.line_label_y_offset);

        this.renderLabel(0);
        this.renderLabel(1);
    },

    getLabelWidth: function(str) {
        if(str && str.length > 0)
            return (str.length / 2) * 9;
        else
            return 10;
    },

    renderLabel: function(id) {
        var x1 = this.parts[id][0][0],
            y1 = this.parts[id][0][1],
            x2 = this.parts[id][1][0],
            y2 = this.parts[id][1][1];

        var cut = id == 0 ? this.obj.conf.line_label_pos_in
                          : this.obj.conf.line_label_pos_out;

        var x = middle(x1, x2, cut),
            y = middle(y1, y2, cut);

        if (this.perfdata === null)
            return;

        var txt = this.perfdata[id][1] + this.perfdata[id][2];

        // Show only bandwidth label
        if (this.obj.conf.line_type == 15)
            txt = this.perfdata[2+id][1] + this.perfdata[2+id][2];

        // Maybe use function to detect the real height in future
        var labelHeight = 21,
            labelWidth  = this.getLabelWidth(txt);

        this.obj.trigger_obj.appendChild(
            renderNagVisTextbox(this.obj.conf.object_id+'-link'+id,
                                '#ffffff', '#000000', (x-labelWidth), parseInt(y - labelHeight / 2),
                                this.obj.conf.z, 'auto', 'auto', '<b>' + txt + '</b>'));

        // Paint the second label in case of line type 14
        if (this.obj.conf.line_type == 14) {
            txt = this.perfdata[2+id][1] + this.perfdata[2+id][2];
            labelWidth = this.getLabelWidth(txt);
            this.obj.trigger_obj.appendChild(
                renderNagVisTextbox(this.obj.conf.object_id+'-link'+(id+1),
                                    '#ffffff', '#000000', (x-labelWidth), parseInt(y + labelHeight / 2),
                                    this.obj.conf.z, 'auto', 'auto', '<b>' + txt + '</b>'));
        }
    },

    renderLinkArea: function() {
        var div = document.createElement('div');
        this.link_area = div;
        div.setAttribute('id', this.obj.conf.object_id+'-link');
        div.style.position = 'absolute';
        div.style.top = '-100px'; // out of screen by default
        div.style.zIndex = parseInt(this.obj.conf.z)+1;
        div.style.width = '10px';
        div.style.height = '10px';

        this.obj.trigger_obj.appendChild(div);
    },

    parsePerfdata: function() {
        this.perfdata = null;

        /*
         Convert perfdata string to structured array. Data returne by this
         function is an array of arrays containing these elements:
           0 = label
           1 = value
           2 = unit of measure (UOM)
           3 = warning
           4 = critical
           5 = minimum
           6 = maximum
        */
        var perf = this.parsePerfdataString();

        if (!perf) {
            this.addWeathermapLineError("Perfdata string is empty");
            return;
        }

        if (!isset(perf[0])) {
            this.addWeathermapLineError("Value 1 is empty");
            return;
        }

        if (!isset(perf[1])) {
            this.addWeathermapLineError("Value 2 is empty");
            return;
        }

        if (this.obj.conf.line_type == 14 || this.obj.conf.line_type == 15) {
            if (!isset(perf[2])) {
                this.addWeathermapLineError("Value 3 is empty");
                return;
            }

            if (!isset(perf[3])) {
                this.addWeathermapLineError("Value 4 is empty");
                return;
            }
        }

        // This is the correct place to handle other perfdata format than the percent value
        // When no UOM is set try to calculate something...
        // This can fix the perfdata values from Check_MKs if and if64 checks.
        // The assumption is that there are perfdata values 'in' and 'out' with byte rates
        // and maximum values given to be able to calculate the percentage usage
        if (perf[0][2] === null || perf[0][2] === ''
           || perf[1][2] === null || perf[1][2] === '') {
            perf = this.calculateUsage(perf);
        }

        this.perfdata = perf;
    },

    addWeathermapLineError: function(e) {
        this.obj.conf.summary_output += ' (Weathermap Line Error: ' + e + ')';
    },

    calcWeathermapColor: function(id) {
        if (!this.perfdata)
            return '#FFCC66';

        if (this.perfdata[id][2] == '%' && this.perfdata[id][1] !== null) {
            return this.getColorFill(this.perfdata[id][1]);
        } else {
            this.obj.conf.summary_output += ' (Weathermap Line Error: Value '
                                            + id +' is not a percentage value)';
            return '#FFCC66';

        }
    },

    /**
     * This function returns the color to use for this line depending on the
     * given percentage usage and on the configured options for this object
     */
    getColorFill: function(perc) {
        var ranges = this.obj.conf.line_weather_colors.split(',');
        // 0 contains the percentage until this color is used
        // 1 contains the color to be used
        for(var i = 0; i < ranges.length; i++) {
            var parts = ranges[i].split(':');
            if(parseFloat(perc) <= parts[0])
                return parts[1];
            parts = null;
        }
        ranges = null;
        return '#000000';
    },

    /**
     * Loops all perfdata sets and searches for labels "in" and "out"
     * with an empty UOM. If found it uses the current value and max value
     * for calculating the percentage usage and also the current usage.
     */
    calculateUsage: function(oldPerfdata) {
        var newPerfdata = [];
        var foundNew = false;

        // Check_MK if/if64 checks support switching between bytes/bits. The detection
        // can be made by some curios hack. The most hackish hack I've ever seen. From hell.
        // Well, let's deal with it.
        var display_bits = false;
        if(oldPerfdata.length >= 11 && oldPerfdata[10][5] == '0.0')
            display_bits = true;

        // This loop takes perfdata with the labels "in" and "out" and uses the current value
        // and maximum values to parse the percentage usage of the line
        for(var i = 0; i < oldPerfdata.length; i++) {
            if(oldPerfdata[i][0] == 'in' && (oldPerfdata[i][2] === null || oldPerfdata[i][2] === '')) {
                newPerfdata[0] = this.perfdataCalcPerc(oldPerfdata[i]);
                if(!display_bits) {
                    newPerfdata[2] = this.perfdataCalcBytesReadable(oldPerfdata[i]);
                } else {
                    oldPerfdata[i][1] *= 8; // convert those hakish bytes to bits
                    newPerfdata[2] = this.perfdataCalcBitsReadable(oldPerfdata[i]);
                }
                foundNew = true;
            }
            if(oldPerfdata[i][0] == 'out' && (oldPerfdata[i][2] === null || oldPerfdata[i][2] === '')) {
                newPerfdata[1] = this.perfdataCalcPerc(oldPerfdata[i]);
                if(!display_bits) {
                    newPerfdata[3] = this.perfdataCalcBytesReadable(oldPerfdata[i]);
                } else {
                    oldPerfdata[i][1] *= 8; // convert those hakish bytes to bits
                    newPerfdata[3] = this.perfdataCalcBitsReadable(oldPerfdata[i]);
                }
                foundNew = true;
            }
        }
        if(foundNew)
            return newPerfdata;
        else
            return oldPerfdata;
    },

    /**
     * Transform bits in a perfdata set to a human readable value
     */
    perfdataCalcBitsReadable: function(set) {
        var KB   = 1024;
        var MB   = 1024 * 1024;
        var GB   = 1024 * 1024 * 1024;
        if(set[1] > GB) {
            set[1] /= GB
            set[2]  = 'Gbit/s'
        } else if(set[1] > MB) {
            set[1] /= MB
            set[2]  = 'Mbit/s'
        } else if(set[1] > KB) {
            set[1] /= KB
            set[2]  = 'Kbit/s'
        } else {
            set[2]  = 'bit/s'
        }
        set[1] = Math.round(set[1]*100)/100;
        return set;
    },

    /**
     * Transform bytes in a perfdata set to a human readable value
     */
    perfdataCalcBytesReadable: function(set) {
        var KB   = 1024;
        var MB   = 1024 * 1024;
        var GB   = 1024 * 1024 * 1024;
        if(set[1] > GB) {
            set[1] /= GB
            set[2]  = 'GB/s'
        } else if(set[1] > MB) {
            set[1] /= MB
            set[2]  = 'MB/s'
        } else if(set[1] > KB) {
            set[1] /= KB
            set[2]  = 'KB/s'
        } else {
            set[2]  = 'B/s'
        }
        set[1] = Math.round(set[1]*100)/100;
        return set;
    },

    /**
     * Calculates the percentage usage of a line when the current value
     *  and the max value are given in the perfdata string
     */
    perfdataCalcPerc: function(set) {
        // Check if all needed information are present
        if(set[1] === null || set[6] === null || set[1] == '' || set[6] == '')
            return set;

        // Calculate percentages with 2 decimals and reset other options
        return Array(set[0], Math.round(set[1]*100/set[6]*100)/100, '%', set[3], set[4], 0, 100);
    },

    /**
     * Split perfdata into mutlidimensional array
     *      Each 1st dimension is a set of perfdata such as 'inUsage=19.34%;85;98')
     *      The 2nd dimension is each set broken apart (label, value, uom, etc.)
     *
     * Inspired by parsePerfdata function by Lars Michelsen which was
     * adapted from PNP process_perfdata.pl.  Thanks to Jörg Linge..
     * The function was originally taken from Nagios::Plugin::Performance
     * Thanks to Gavin Carr and Ton Voon
     *
     * @author      Greg Frater <greg@fraterfactory.com>
     *
     */
    parsePerfdataString: function() {
        var parsed = [];

        var perfdata = this.obj.conf.perfdata;
        if (!perfdata)
            return [];

        // Clean up perfdata
        perfdata = perfdata.replace('/\s*=\s*/', '=');

        // Break perfdata string into array of individual sets
        var re = /([^=]+)=([\d\.\-]+)([\w%]*);?([\d\.\-:~@]+)?;?([\d\.\-:~@]+)?;?([\d\.\-]+)?;?([\d\.\-]+)?\s*/g;
        var perfdataMatches = perfdata.match(re);

        // Check for empty perfdata
        if (perfdataMatches == null)
            return [];

        // Break perfdata parts into array
        for (var i = 0; i < perfdataMatches.length; i++) {
            // Get parts of perfdata from string
            var tmpSetMatches = perfdataMatches[i].match(/(&#145;)?([\w\s\=\']*)(&#145;)?\=([\d\.\-\+]*)([\w%]*)[\;|\s]?([\d\.\-:~@]+)*[\;|\s]?([\d\.\-:~@]+)*[\;|\s]?([\d\.\-\+]*)[\;|\s]?([\d\.\-\+]*)/);

            // Check if we got any perfdata
            if (tmpSetMatches === null)
                continue;

            parsed.push([
                tmpSetMatches[2], // label
                tmpSetMatches[4], // value
                tmpSetMatches[5], // UOM
                tmpSetMatches[6], // warn
                tmpSetMatches[7], // crit
                tmpSetMatches[8], // min
                tmpSetMatches[9], // max
            ]);
        }
        return parsed;
    }

});
