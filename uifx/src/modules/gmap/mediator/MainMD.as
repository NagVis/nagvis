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

package modules.gmap.mediator
{
	import flash.events.IEventDispatcher;
	import flash.external.ExternalInterface;
	import flash.system.Security;
	
	import modules.gmap.data.LinksData;
	import modules.gmap.data.LocationsData;
	import modules.gmap.domain.Link;
	import modules.gmap.domain.Location;
	import modules.gmap.domain.Settings;
	import modules.gmap.domain.nagios.Host;
	import modules.gmap.domain.nagios.HostGroup;
	import modules.gmap.domain.nagios.Service;
	import modules.gmap.domain.nagios.ServiceGroup;
	import modules.gmap.events.LinkEvent;
	import modules.gmap.events.ModeEvent;
	import modules.gmap.events.SetupEvent;
	import modules.gmap.view.MainView;

	import mx.core.Application;

	public class MainMD
	{
		public static const MODE_DEFAULT : int = 0;
		public static const MODE_LOCATION_EDIT : int = 1;
		public static const MODE_LOCATION_SEARCH : int = 2;
		public static const MODE_LINK_EDIT : int = 3;

		private var _mode:int = 0;

		private var _view : MainView;
		private var _dispatcher : IEventDispatcher;

		public function MainMD(view : MainView, dispatcher : IEventDispatcher)
		{
			this._view = view;
			this._dispatcher = dispatcher;
		}

		public function init() : void
		{
			Security.allowInsecureDomain("*");
		}

		public function get mode() : int
		{
			return _mode;
		}

		public function set mode(value : int) : void
		{
			if (_mode !== value)
			{
				_dispatcher.dispatchEvent(
					new ModeEvent(ModeEvent.CHANGED, _mode, value)
				);

				_mode = value;
			}
		}

		public function reconsiderMode() : void
		{
			switch (_view.ebg.current)
			{
				case _view.locationBox:
					mode = MODE_LOCATION_EDIT;
					break;
				case _view.searchBox:
					mode = MODE_LOCATION_SEARCH;
					break;
				case _view.linksBox:
					mode = MODE_LINK_EDIT;
					break;
				default:
					mode = MODE_DEFAULT;
			}
		}

		public function selectLocation(location : Location) : void
		{
			if (_mode == MODE_LOCATION_SEARCH)
			{
				//no location selected - do nothing
				if (!location) 
					return;
				
				//selected existing location - do nothing
				if (location.id && location.id.length > 0)
					return;
					
				//selected the new location - open location dialog to add it
				_view.locationBox.setCurrentState('right-expanded');
				return;
			}
			
			if (_mode == MODE_LINK_EDIT)
			{
				if(location)
					_view.linksBox.pushLocation(location);
			}
		}

		public function selectLink(link : Link) : void
		{
		}

		public function gotoURL(url : String, newWindow : Boolean) : void
		{
			if (newWindow)
				ExternalInterface.call('window.open("' + url + '")');
			else
				ExternalInterface.call('window.location.assign("' + url + '")');
		}
		
		public function removeRelatedLinks(locationID:String, links:LinksData):void
		{
			for(var i:int = links.length - 1; i >=0; --i)
			{
				var link:Link = links.getItemAt(i) as Link;
				if(link.id1 == locationID || link.id2 == locationID)
					_dispatcher.dispatchEvent(
						new LinkEvent(LinkEvent.DELETE, link)
					);
			}
		}

		public function activate(element : Object, settings : Settings) : void
		{
			var slices : Array;

			//paranoid check
			if(element == null)
				return;

			if (element.action == null || element.action == "")
				slices = settings.defaultLocationAction.split(':', 2);
			else
				slices = element.action.split(':', 2);

			switch (slices[0])
			{
				case '':
					return;

				case 'nagios':
					var nagiosUrl : String;
					if (element.object is Host)
					{
						nagiosUrl = settings.hosturl;
						nagiosUrl = nagiosUrl.replace(/\[host_name]/, element.object.name);
					}
					else if (element.object is HostGroup)
					{
						nagiosUrl = settings.hostgroupurl;
						nagiosUrl = nagiosUrl.replace(/\[hostgroup_name]/, element.object.name);
					}
					else if (element.object is Service)
					{
						nagiosUrl = settings.serviceurl;
						nagiosUrl = nagiosUrl.replace(/\[host_name]/, element.object.host);
						nagiosUrl = nagiosUrl.replace(/\[service_description]/, element.object.description);
					}
					else if (element.object is ServiceGroup)
					{
						nagiosUrl = settings.servicegroupurl;
						nagiosUrl = nagiosUrl.replace(/\[servicegroup_name]/, element.object.name);
					}
					else
						return; // this should not ever happen

					nagiosUrl = nagiosUrl.replace(/\[htmlbase]/, settings.htmlbase);
					nagiosUrl = nagiosUrl.replace(/\[htmlcgi]/, settings.htmlcgi);
					gotoURL(nagiosUrl, settings.openLinksInNewWindow);
					return;

				case 'nagvis':
					var nagvisUrl : String = settings.mapurl;
					nagvisUrl = nagvisUrl.replace(/\[htmlbase]/, settings.htmlbase);
					nagvisUrl = nagvisUrl.replace(/\[htmlcgi]/, settings.htmlcgi);
					nagvisUrl = nagvisUrl.replace(/\[map_name]/, slices[1]);
					gotoURL(nagvisUrl, settings.openLinksInNewWindow);
					return;

				default:
					gotoURL(element.action, settings.openLinksInNewWindow);
					return;
			}
		}

		public function doSetup():void
		{
			for(var option:String in Application.application.parameters)
			{
				var value:String = Application.application.parameters[option];

				_dispatcher.dispatchEvent(
					new SetupEvent(option, value)
				);
			}
		}
	}
}
