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

	import modules.gmap.domain.Location;

	public class LocationEvent extends Event
	{
		public static const SELECTED : String = "LocationSelected";
		public static const CHANGE : String = "LocationChange";
		public static const ADD : String = "LocationAdd";
		public static const SAVE : String = "LocationSave";
		public static const DELETE : String = "LocationDelete";
		public static const ACTIVATE : String = "LocationActivate";

		public var location : Location;

		public function LocationEvent(type : String, location : Location = null, bubbles:Boolean = true, cancelable : Boolean = false)
		{
			super(type, bubbles, cancelable);
			this.location = location;
		}

        override public function clone() : Event
        {
			return new LocationEvent(this.type, this.location, this.bubbles, this.cancelable);
        }
	}
}

