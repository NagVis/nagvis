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

package modules.gmap.domain
{
	[Bindable]
	[RemoteClass(alias="Link")]
	public class Link
	{
		public static const STATE_UNKNOWN : Number = 0;
		public static const STATE_OK : Number = 1;
		public static const STATE_WARNING : Number = 2;
		public static const STATE_ERROR : Number = 3;

		public var id : String;

		public var id1 : String;
		public var location1 : Location;

		public var id2 : String;
		public var location2:Location;

		public var description : String;
		public var action : String;
		public var object : Object;
		public var state : Number;

		public function update(value : Link) : void
		{
			this.id = value.id;
			this.location1 = value.location1;
			this.location2 = value.location2;
			this.id1 = value.id1;
			this.id2 = value.id2;
			this.description = value.description;
			this.action = value.action;
			this.object = value.object;
			this.state = value.state;
		}
		
		public function get label():String
		{
			if(location1 && location2)
				return location1.label + ' - ' + location2.label;
				
			return 'Invalid Link';			
		}
	}
}
