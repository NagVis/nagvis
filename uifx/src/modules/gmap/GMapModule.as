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

import com.google.maps.LatLng;
import com.google.maps.Map;
import com.google.maps.MapEvent;
import com.google.maps.controls.ZoomControl;
import com.google.maps.overlays.Polyline;

import flash.events.Event;
import flash.events.IOErrorEvent;
import flash.net.URLLoader;
import flash.system.Security;

import modules.gmap.LocationsCollection;
import modules.gmap.LocationsView;
import modules.gmap.domain.Link;
import modules.gmap.domain.Location;
import modules.gmap.domain.Viewpoint;
import modules.gmap.domain.nagios.Host;
import modules.gmap.domain.nagios.HostGroup;
import modules.gmap.domain.nagios.Service;
import modules.gmap.domain.nagios.ServiceGroup;
import modules.gmap.events.LocationEvent;
import modules.gmap.events.LocationsViewEvent;

import mx.collections.ArrayCollection;
import mx.controls.Alert;
import mx.events.ListEvent;
import mx.rpc.events.FaultEvent;
import mx.rpc.events.ResultEvent;
import mx.utils.StringUtil;

/*********************************************/
/* Global objects
/*********************************************/

private var map : Map;
private var key : String;

private var viewpoints     : ArrayCollection;
private var locations      : LocationsCollection = new LocationsCollection;
private var foundLocations : LocationsCollection = new LocationsCollection;
private var links          : ArrayCollection;
private var hosts          : ArrayCollection = new ArrayCollection;
private var hostgroups     : ArrayCollection = new ArrayCollection;
private var services       : ArrayCollection = new ArrayCollection;
private var servicegroups  : ArrayCollection = new ArrayCollection;

private var locationsView      : LocationsView;
private var foundLocationsView : FoundLocationsView;

/*********************************************/
/* Initialization
/*********************************************/

private function init() : void
{
	Security.allowInsecureDomain("*");

	var keyLoader : URLLoader  = new URLLoader();
	var keyURL    : URLRequest = new URLRequest("GoogleMaps.key");

	keyLoader.addEventListener(Event.COMPLETE, function() : void {
		key = StringUtil.trim(keyLoader.data);
		initMap();
	});
	keyLoader.addEventListener(IOErrorEvent.IO_ERROR, function() : void {
		Alert.show("Error loading GoogleMaps.key\n\n"
			+ "Accessing the free Google Maps API requires the specification of an API key linked to a base URL.\n"
			+ "You can obtain a free API key from Google at http://www.google.com/apis/maps/signup.html",
			"Error");
	});

	keyLoader.load(keyURL);
}

private function initMap() : void
{
	map = new Map();
	map.key = key;
	map.width = mapContainer.width;
	map.height = mapContainer.height;
	map.addEventListener(MapEvent.MAP_READY, onMapReady);
	mapContainer.addChild(map);
}

private function onLocationsChange(event : LocationEvent) : void
{
	Alert.show("LocationEvent catched, event.location.id = " + event.location.id);
}

private function onMapReady(event : Event) : void
{
  	map.enableScrollWheelZoom();
	map.enableContinuousZoom();
	map.addControl(new ZoomControl());
	map.setZoom(2);

	locationsView = new LocationsView(map, locations);
	locationsView.addEventListener(LocationsViewEvent.SELECT_LOCATION, onSelectLocation);

	foundLocationsView = new FoundLocationsView(map, foundLocations);
	foundLocationsView.addEventListener(LocationsViewEvent.SELECT_LOCATION, onSelectFoundLocation);

	/* TODO: reenable
	rDatabase.getHosts();
	rDatabase.getServices();
	rDatabase.getHostGroups();
	rDatabase.getServiceGroups();
	rViewpoints.getAll();
	rLocations.getAll(); 
	*/
	// Note: rLinks.getAll() is called at the end of getLocations_handler
	//       due to asynchronous nature of remote calls
}

/*********************************************/
/* Remoting
/*********************************************/

private function fault(event : FaultEvent) : void
{
	var msg : String = event.fault.faultString as String;

 	msg = msg.replace("java.lang.Exception :", "");
	Alert.show(msg, "Error");
}

private function getViewpoints_handler(event : ResultEvent) : void
{
	var result : ArrayCollection = new ArrayCollection(event.result as Array);

	viewpoints = new ArrayCollection();
	for each (var viewpoint : Viewpoint in result)
		viewpoints.addItem(viewpoint);

	viewpointBox.list.dataProvider = viewpoints;
}

private function addViewpoint_handler(event : ResultEvent) : void
{
	var result : Viewpoint = event.result as Viewpoint;

	viewpoints.addItem({label: result.label, center: LatLng.fromUrlValue(result.center), zoom: result.zoom});
}

private function getLocations_handler(event : ResultEvent) : void
{
	var result : ArrayCollection = new ArrayCollection(event.result as Array);

	for each (var location : Location in result)
	{
		locations.addUpdateItem(location);
		//i.addEventListener(LocationEvent.CHANGE, onLocationsChange);
	}

	linksBox.point1.dataProvider = locations;
	linksBox.point2.dataProvider = locations;
	//linksBox.services.dataProvider = services;

	locationsView.showLocations();

	/* TODO: reenable
	rLinks.getAll();
	*/
}

private function addLocation_handler(event : ResultEvent) : void
{
	var result : Location = event.result as Location;

	locations.addItem(result);

	locationBox.setCurrentState("right-contracted");
}

private function editLocation_handler(event : ResultEvent) : void
{
	var result : Location = event.result as Location;

	locations.setItemAt(result, locations.getItemIndex(locations.getItemById(result.id)));

	locationBox.setCurrentState("right-contracted");
}

private function removeLocation_handler(event : ResultEvent) : void
{
	var result : String = event.result as String;

	locations.removeItemAt(locations.getItemIndex(locations.getItemById(result)));

	locationBox.setCurrentState("right-contracted");
}

private function findLocation_handler(event : ResultEvent) : void
{
	var result : ArrayCollection = new ArrayCollection(event.result as Array);

	for each (var location : Location in result)
	{
		// found locations that already exist should get their IDs assigned
		var existingLocation : Location = locations.getItemByLatLng(LatLng.fromUrlValue(location.point));
		if (existingLocation)
		{
			location.id = existingLocation.id;
			location.label = existingLocation.label;
			location.address = existingLocation.address;
			location.description = existingLocation.description;
		}

		foundLocations.addItem(location);
	}

	foundLocationsView.showLocations();
}

private function getLinks_handler(event : ResultEvent) : void
{
	var result : ArrayCollection = new ArrayCollection(event.result as Array);

	links = new ArrayCollection();
	for each (var link : Link in result)
		links.addItem(link);

	showLinks();
}

private function addLink_handler(event : ResultEvent) : void
{
	var result : Link = event.result as Link;

	links.addItem(result);

	drawLink(result);

	linksBox.setCurrentState("right-contracted");
}

private function getHosts_handler(event : ResultEvent) : void
{
	var result : ArrayCollection = new ArrayCollection(event.result as Array);

	hosts = new ArrayCollection();
	for each (var host : Host in result)
		hosts.addItem(host);

	locationBox.hosts = hosts;
}

private function getServices_handler(event : ResultEvent) : void
{
	var result : ArrayCollection = new ArrayCollection(event.result as Array);

	services = new ArrayCollection();
	for each (var service : Service in result)
		services.addItem(service);

	locationBox.services = services;
	//linksBox.services.dataProvider = services;
}

private function getHostGroups_handler(event : ResultEvent) : void
{
	var result : ArrayCollection = new ArrayCollection(event.result as Array);

	hostgroups = new ArrayCollection();
	for each (var hostgroup : HostGroup in result)
		hostgroups.addItem(hostgroup);

	locationBox.hostgroups = hostgroups;
}

private function getServiceGroups_handler(event : ResultEvent) : void
{
	var result : ArrayCollection = new ArrayCollection(event.result as Array);

	servicegroups = new ArrayCollection();
	for each (var servicegroup : ServiceGroup in result)
		servicegroups.addItem(servicegroup);

	locationBox.servicegroups = servicegroups;
}

/*********************************************/
/* Location markers
/*********************************************/

private function drawLink(link : Link) : void
{
	var point1 : String = "";
	var point2 : String = "";

	if (!locations)
		return;

	for each (var location1 : Location in locations)
		if (location1.id == link.id1)
		{
			point1 = location1.point;
			break;
		}		

	for each (var location2 : Location in locations)
		if (location2.id == link.id2)
		{
			point2 = location2.point;
			break;
		}		

	if (point1 != "" && point2 != "")
		map.addOverlay(new Polyline([LatLng.fromUrlValue(point1), LatLng.fromUrlValue(point2)]));
}

private function showLinks() : void
{
	if (!locations)
		return;

	for each (var link : Link in links)
		drawLink(link);
}

/*********************************************/
/* Search location by address
/*********************************************/

private function onLocateAddress(address : String) : void
{
	locationsView.hideLocations();
	/*
	rLocations.find(address);
	*/
}

private function onHideSearchBox() : void
{
	if (locationBox.status == "contracted")
	{
		if (foundLocationsView && !foundLocationsView.selectedLocation)
			foundLocationsView.clearLocations();

		if (locationsView)
			locationsView.showLocations();
	}
}

/*********************************************/
/* Location dialog box
/*********************************************/

private function onSelectLocation(event : LocationsViewEvent) : void
{
	if (locationBox.status == "contracted")
		locationBox.setCurrentState("right-expanded");
}

private function onSelectFoundLocation(event : LocationsViewEvent) : void
{
	locationBox.setCurrentState("right-expanded");
}

private function onShowLocationBox() : void
{
	if (searchBox.status == "expanded")
		searchBox.setCurrentState("right-contracted");

	/*
	for each (var host : Host in hosts)
next_host:
		for each (var location : Location in locations)
			for each (var locationhost : Array in location.hosts)
				if (locationhost.id == host.id)
				{
					host.selected = true;
					break next_host;
				}
	*/

	if (locationsView && locationsView.selectedLocation)
		locationBox.update(locationsView.selectedLocation);
	else if(foundLocationsView && foundLocationsView.selectedLocation)
		locationBox.update(foundLocationsView.selectedLocation);
	else
		locationBox.update(null);
}

private function onHideLocationBox() : void
{
	if (foundLocationsView && foundLocationsView.selectedLocation)
	{
		foundLocationsView.clearLocations();
		locationsView.showLocations();
	}
	else if (locationsView && locationsView.selectedLocation)
		locationsView.unselectLocation();
}

private function onSaveLocation() : void
{
	if (locationBox.locID != "")
	{
		/* TODO: reenable
		rLocations.edit(locationBox.locID,
			(new LatLng(parseFloat(locationBox.locLat.text), parseFloat(locationBox.locLng.text)).toUrlValue(16)),
			locationBox.locName.text, locationBox.locAddress.text, locationBox.locDescription.text,
			locationBox.locNObject.selectedItem);
		*/
	}
	else
	{
		/* TODO: reenable
		rLocations.add((new LatLng(parseFloat(locationBox.locLat.text), parseFloat(locationBox.locLng.text)).toUrlValue(16)),
			locationBox.locName.text, locationBox.locAddress.text, locationBox.locDescription.text,
			locationBox.locNObject.selectedItem);
		*/
	}
}

private function onDeleteLocation() : void
{
	/* TODO: reenable
	if (locationBox.locID != "")
		rLocations.remove(locationBox.locID);
	*/
}

/*********************************************/
/* Link dialog box
/*********************************************/

private function onShowLinkBox() : void
{
	/*
	for each (var service : Service in services)
next_service:
		for each (var link : Link in links)
			for each (var linkservice : Array in link.services)
				if (linkservice.id == service.id)
				{
					service.selected = true;
					break next_service;
				}
	*/
}

private function onLink() : void
{
	var location1 : Location = linksBox.point1.selectedItem as Location;
	var location2 : Location = linksBox.point2.selectedItem as Location;

	if (!location1 || !location2)
		return;

	if (location1 == location2)
	{
		Alert.show("You must select different locations", "Error");
		return;
	}

	var exists : Boolean = false;
	for each (var link : Link in links)
	{
		if (location1.id == link.id1 && location2.id == link.id2)
		{
			exists = true;
			break;
		}
		else if (location1.id == link.id2 && location2.id == link.id1)
		{
			exists = true;
			break;
		}
	}

	if (!exists)
	{
		/* TODO: reenable
		rLinks.add(location1.id, location2.id);
		*/
	}
	else
	{
		Alert.show("The link between selected locations already exists", "Error");
		return;
	}
}

/*********************************************/
/* Viewpoints dialog box
/*********************************************/

private function onSaveViewpoint(name : String) : void
{
	/* TODO: reenable
	rViewpoints.add(name, map.getCenter().toUrlValue(16), map.getZoom());
	*/
}

private function onSelectViewpoint(event : ListEvent) : void
{
	var viewpoint : Viewpoint = viewpointBox.list.selectedItem as Viewpoint;
	map.setCenter(LatLng.fromUrlValue(viewpoint.center));
	map.setZoom(viewpoint.zoom);
}
