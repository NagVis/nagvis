/*****************************************************************************
 *
 * NagVisObject.js - This class handles the visualisation of statefull objects
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

/**
 * @author	Lars Michelsen <lm@larsmichelsen.com>
 */

var NagVisStatefulObject = NagVisObject.extend({
    has_state: true,

    // Stores the information from last refresh (Needed for change detection)
    last_state: null,
    // Array of member objects like services for hosts etc.
    members: [],
    // Timestamps for last event handling events if repeated events are enabled
    event_time_first: null,
    event_time_last: null,

    constructor: function(oConf) {
        this.base(oConf);
    },

    getMembers: function() {
        // Clear member array on every launch
        this.members = [];

        if(this.conf && this.conf.members && this.conf.members.length > 0) {
            for(var i = 0, len = this.conf.members.length; i < len; i++) {
                var oMember = this.conf.members[i];
                var oObj;

                switch (oMember.type) {
                    case 'host':
                        oObj = new NagVisHost(oMember);
                    break;
                    case 'service':
                        oObj = new NagVisService(oMember);
                    break;
                    case 'hostgroup':
                        oObj = new NagVisHostgroup(oMember);
                    break;
                    case 'servicegroup':
                        oObj = new NagVisServicegroup(oMember);
                    break;
                    case 'dyngroup':
                        oObj = new NagVisDynGroup(oMember);
                    break;
                    case 'aggr':
                        oObj = new NagVisAggr(oMember);
                    break;
                    case 'map':
                        oObj = new NagVisMap(oMember);
                    break;
                    case 'textbox':
                        oObj = new NagVisTextbox(oMember);
                    break;
                    case 'container':
                        oObj = new NagVisContainer(oMember);
                    break;
                    case 'shape':
                        oObj = new NagVisShape(oMember);
                    break;
                    case 'line':
                        oObj = new NagVisLine(oMember);
                    break;
                    default:
                        alert('Error: Unknown member object type ('+oMember.type+')');
                    break;
                }

                if(oObj !== null) {
                    this.members.push(oObj);
                }

                oObj = null;
                oMember = null;
            }
        }
    },

    getStatefulMembers: function() {
        var stateful = [];
        for (var i = 0, len = this.members.length; i < len; i++) {
            if (this.members[i].has_state) {
                stateful.push(this.members[i]);
            }
        }
        return stateful;
    },

    /**
     * PUBLIC saveLastState()
     *
     * Saves the current state in last state array for later change detection
     *
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    saveLastState: function() {
        this.last_state = {
          'summary_state': this.conf.summary_state,
            'summary_in_downtime': this.conf.summary_in_downtime,
            'summary_stale': this.conf.summary_stale,
            'summary_problem_has_been_acknowledged': this.conf.summary_problem_has_been_acknowledged,
            'output': this.conf.output,
            'perfdata': this.conf.perfdata
        };
    },

    /**
     * PUBLIC stateChanged()
     *
     * Check if a state change occured since last refresh
     *
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    stateChanged: function() {
        if(this.conf.summary_state != this.last_state.summary_state ||
           this.conf.summary_problem_has_been_acknowledged != this.last_state.summary_problem_has_been_acknowledged ||
           this.conf.summary_stale != this.last_state.summary_stale ||
           this.conf.summary_in_downtime != this.last_state.summary_in_downtime) {
            return true;
        } else {
            return false;
        }
    },

    /**
     * PUBLIC stateChangedToWorse()
     *
     * Check if a state change occured to a worse state
     *
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    stateChangedToWorse: function() {
        var lastSubState = 'normal';
        if(this.last_state.summary_problem_has_been_acknowledged && this.last_state.summary_problem_has_been_acknowledged === 1) {
            lastSubState = 'ack';
        } else if(this.last_state.summary_in_downtime && this.last_state.summary_in_downtime == 1) {
            lastSubState = 'downtime';
        } else if(this.last_state.summary_stale) {
            lastSubState = 'stale';
        }

        // If there is no "last state" return true here
        if(!this.last_state.summary_state) {
            return true;
        }

        var lastWeight = oStates[this.last_state.summary_state][lastSubState];

        var subState = 'normal';
        if(this.conf.summary_problem_has_been_acknowledged && this.conf.summary_problem_has_been_acknowledged === 1) {
            subState = 'ack';
        } else if(this.conf.summary_in_downtime && this.conf.summary_in_downtime === 1) {
            subState = 'downtime';
        } else if(this.conf.summary_stale) {
            subState = 'stale';
        }

        var weight = oStates[this.conf.summary_state][subState];

        return lastWeight < weight;
    },

    /**
     * Returns true if the object is in non acked problem state
     */
    hasProblematicState: function() {
        // In case of acked/downtimed states this is no problematic state
        if(this.conf.summary_problem_has_been_acknowledged && this.conf.summary_problem_has_been_acknowledged === 1) {
            return false;
        } else if(this.conf.summary_in_downtime && this.conf.summary_in_downtime === 1) {
            return false;
        } else if(this.conf.summary_stale && this.conf.summary_stale) {
            return false;
        }
        
        var weight = oStates[this.conf.summary_state]['normal'];
        return weight > oStates['UP']['normal'];
    },

    /**
     * PUBLIC outputChanged()
     *
     * Check if an output/perfdata change occured since last refresh
     *
     * @author	Lars Michelsen <lm@larsmichelsen.com>
     */
    outputOrPerfdataChanged: function() {
        return this.conf.output != this.last_state.output || this.conf.perfdata != this.last_state.perfdata;
    },

    // Updates the logical elements of the object
    update: function () {
        this.getMembers();
        this.replaceMacros();

        if (g_view.type === 'map') {
            switch (this.conf.view_type) {
                case 'line':
                    new ElementLine(this).addTo(this);
                break;
                case 'gadget':
                    new ElementGadget(this).addTo(this);
                break;
                default:
                    new ElementIcon(this).addTo(this);
                break;
            }
        }

        this.base();
    },

    updateAttrs: function(attrs, only_state) {
        // Save old state for later "change detection"
        this.saveLastState();

        this.base(attrs, only_state);
        this.updateMemberAttrs(attrs, only_state);

        if (!only_state) {
            this.replaceMacros();
        }

        // When the config has not changed, but the state, rerender the whole object
        // the config update is handled within NagVisObject()
        if (only_state && this.stateChanged())
            this.render(); // erase, rerender and draw object

        if (this.stateChanged()) {
            /**
             * Additional eventhandling
             *
             * event_log=1/0
             * event_highlight=1/0
             * event_scroll=1/0
             * event_sound=1/0
             */

            // Only do eventhandling when object state changed to a worse state
            if (this.stateChangedToWorse()) {
                this.raiseEvents(true);
                this.initRepeatedEvents();
            }
            return true;
        }
        else {
            return false;
        }
    },

    transformAttributes: function() {
        this.replaceMacros();
    },

    updateMemberAttrs: function(attrs, only_state) {
        if (!this.members)
            return;

        // Update already existing objects
        for (var i = 0, len = attrs.members.length; i < len; i++) {
            var member_attrs = attrs.members[i],
                updated = false;

            for (var a = 0, len2 = this.members.length; a < len2; a++) {
                var member = this.members[a];
                if (member_attrs.object_id == member.conf.object_id) {
                    member.updateAttrs(member_attrs, only_state);
                    break;
                }
            }
        }

        this.getMembers();
    },

    /**
     * Raise enabled frontend events for the object with the given object id
     */
    raiseEvents: function (stateChanged) {
        // - Highlight (Flashing)
        if (oPageProperties.event_highlight === '1') {
            if (this.conf.view_type && this.conf.view_type === 'icon') {
                // Detach the handler
                setTimeout(function(obj_id) {
                    return function() {
                        flashIcon(obj_id, oPageProperties.event_highlight_duration,
                                  oPageProperties.event_highlight_interval);
                    };
                }(this.conf.object_id), 0);
            } else {
                // FIXME: Atm only flash icons, not lines or gadgets
            }
        }
    
        // - Scroll to object
        if (oPageProperties.event_scroll === '1') {
            setTimeout(function(x, y) {
                return function() {
                    scrollSlow(x, y, 1);
                };
            }(this.parsedX(), this.parsedY()), 0);
        }
    
        // - Eventlog
        if (this.conf.type == 'service') {
            if (stateChanged) {
                eventlog("state-change", "info", this.conf.type+" "+this.conf.name+" "+this.conf.service_description+": Old: "+this.last_state.summary_state+"/"+this.last_state.summary_problem_has_been_acknowledged+"/"+this.last_state.summary_in_downtime+" New: "+this.conf.summary_state+"/"+this.conf.summary_problem_has_been_acknowledged+"/"+this.conf.summary_in_downtime);
            }
            else {
                eventlog("state-log", "info", this.conf.type+" "+this.conf.name+" "+this.conf.service_description+": State: "+this.conf.summary_state+"/"+this.conf.summary_problem_has_been_acknowledged+"/"+this.conf.summary_in_downtime);
            }
        }
        else {
            if (stateChanged) {
                eventlog("state-change", "info", this.conf.type+" "+this.conf.name+": Old: "+this.last_state.summary_state+"/"+this.last_state.summary_problem_has_been_acknowledged+"/"+this.last_state.summary_in_downtime+" New: "+this.conf.summary_state+"/"+this.conf.summary_problem_has_been_acknowledged+"/"+this.conf.summary_in_downtime);
            }
            else {
                eventlog("state-log", "info", this.conf.type+" "+this.conf.name+": State: "+this.conf.summary_state+"/"+this.conf.summary_problem_has_been_acknowledged+"/"+this.conf.summary_in_downtime);
            }
        }
    
        // - Sound
        if (oPageProperties.event_sound === '1') {
            setTimeout(function(obj_id) {
                return function() {
                    playSound(obj_id, 1);
                };
            }(this.conf.object_id), 0);
        }
    },

    // Initializes repeated events (if configured to do so) after first event handling
    initRepeatedEvents: function () {
        // Are the events configured to be re-raised?
        if(isset(oViewProperties.event_repeat_interval)
           && oViewProperties.event_repeat_interval != 0) {
            this.event_time_first = iNow;
            this.event_time_last  = iNow;
        }
    },

    /**
     * Checks wether or not repeated events need to be re-raised and re-raises
     * them if the time has come
     */
    checkRepeatEvents: function () {
        // Terminate repeated events after the state has changed to OK state
        if (!this.hasProblematicState()) {
            // Terminate, reset vars
            this.event_time_first = null;
            this.event_time_last  = null;
            return;
        }
    
        // Terminate repeated events after duration has been reached when
        // a limited duration has been configured
        if (oViewProperties.event_repeat_duration != -1 
           && this.event_time_first 
              + oViewProperties.event_repeat_duration < iNow) {
            // Terminate, reset vars
            this.event_time_first = null;
            this.event_time_last  = null;
            return;
        }
        
        // Time for next event interval?
        if (this.event_time_last
           + oViewProperties.event_repeat_interval >= iNow) {
            this.raiseEvents(false);
            this.event_time_last = iNow;
        }
    },

    /**
     * Replaces macros of urls and hover_urls
     *
     * @author 	Lars Michelsen <lm@larsmichelsen.com>
     */
    replaceMacros: function () {
        var name = '';
        if(this.conf.type == 'service') {
            name = 'host_name';
        } else {
            name = this.conf.type + '_name';
        }

        if(this.conf.url && this.conf.url !== '') {
            if(this.conf.htmlcgi && this.conf.htmlcgi !== '') {
                this.conf.url = this.conf.url.replace(getRegEx('htmlcgi', '\\[htmlcgi\\]', 'g'), this.conf.htmlcgi);
            } else {
                this.conf.url = this.conf.url.replace(getRegEx('htmlcgi', '\\[htmlcgi\\]', 'g'), oGeneralProperties.path_cgi);
            }

            this.conf.url = this.conf.url.replace(getRegEx('htmlbase', '\\[htmlbase\\]', 'g'), oGeneralProperties.path_base);

            this.conf.url = this.conf.url.replace(getRegEx(name, '\\['+name+'\\]', 'g'), this.conf.name);
            if(this.conf.type == 'service') {
                this.conf.url = this.conf.url.replace(getRegEx('service_description', '\\[service_description\\]', 'g'), this.conf.service_description);
            }

            if(this.conf.type != 'map') {
                this.conf.url = this.conf.url.replace(getRegEx('backend_id', '\\[backend_id\\]', 'g'), this.conf.backend_id);
            }
        }
    },

    highlight: function(show) {
        // FIXME: Highlight lines in the future too
        if(this.conf.view_type !== 'icon')
            return;

        var oObjIcon = document.getElementById(this.conf.object_id + '-icon');
        var oObjIconDiv = document.getElementById(this.conf.object_id + '-icondiv');

        var sColor = oStates[this.conf.summary_state].color;

        this.bIsFlashing = show;
        if(show) {
            oObjIcon.style.border  = "5px solid " + sColor;
            oObjIconDiv.style.top  = (this.parseCoord(this.conf.y, 'y') - 5) + 'px';
            oObjIconDiv.style.left = (this.parseCoord(this.conf.x, 'x') - 5) + 'px';
        } else {
            oObjIcon.style.border  = "none";
            oObjIconDiv.style.top  = this.parseCoord(this.conf.y, 'y') + 'px';
            oObjIconDiv.style.left = this.parseCoord(this.conf.x, 'x') + 'px';
        }

        sColor      = null;
        oObjIconDiv = null;
        oObjIcon    = null;
    },

});
