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

	import modules.gmap.domain.Viewpoint;

	public class ViewpointEvent extends Event
	{
		public static const SELECTED : String = "ViewpointSelected";
		public static const CREATE : String = "ViewpointCreate";
		public static const CREATED : String = "ViewpointCreated";
		public static const SAVE : String = "ViewpointSave";
		public static const DELETE : String = "ViewpointDelete";

		public var viewpoint : Viewpoint;

		public function ViewpointEvent(type : String, viewpoint : Viewpoint = null, bubbles : Boolean = true, cancelable : Boolean=false)
		{
			super(type, bubbles, cancelable);
			this.viewpoint = viewpoint;
		}
	}
}
