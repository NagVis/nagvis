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
	[RemoteClass(alias="Settings")]
	public class Settings
	{
		public var googleMapsKey : String;
		public var defaultLocationAction : String;
		public var openLinksInNewWindow : Boolean;
		public var htmlbase : String;
		public var htmlcgi : String;
		public var hosturl : String;
		public var hostgroupurl : String;
		public var serviceurl : String;
		public var servicegroupurl : String;
		public var mapurl : String;

		public function Settings(googleMapsKey : String = "",
			defaultLocationAction : String = "",
			openLinksInNewWindow : Boolean = false,
			htmlbase : String = "",
			htmlcgi : String = "",
			hosturl : String = "",
			hostgroupurl : String = "",
			serviceurl : String = "",
			servicegroupurl : String = "",
			mapurl : String = "") : void
		{
			this.googleMapsKey = googleMapsKey;
			this.defaultLocationAction = defaultLocationAction;
			this.openLinksInNewWindow = openLinksInNewWindow;
			this.htmlbase = htmlbase;
			this.htmlcgi = htmlcgi;
			this.hosturl = hosturl;
			this.hostgroupurl = hostgroupurl;
			this.serviceurl = serviceurl;
			this.servicegroupurl = servicegroupurl;
			this.mapurl = mapurl;
		}
	}
}
