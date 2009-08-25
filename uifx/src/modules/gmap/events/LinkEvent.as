/*****************************************************************************
 *
 * Copyright (C) 2009 NagVis Project
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

package modules.gmap.events
{
	import flash.events.Event;

	import modules.gmap.domain.Link;

	public class LinkEvent extends Event
	{
		public static const SELECTED : String = "LinkSelected";
		public static const TRYON : String = "LinkTryOn";
		public static const CHANGE : String = "LinkChange";
		public static const ADD : String = "LinkAdd";
		public static const SAVE : String = "LinkSave";
		public static const DELETE : String = "LinkDelete";
		public static const ACTIVATE : String = "LinkActivate";

		public var link : Link;

		public function LinkEvent(type : String, link : Link = null, bubbles : Boolean = true, cancelable : Boolean = false)
		{
			super(type, bubbles, cancelable);
			this.link = link;
		}

        override public function clone() : Event
        {
			return new LinkEvent(this.type, this.link, this.bubbles, this.cancelable);
        }
	}
}
