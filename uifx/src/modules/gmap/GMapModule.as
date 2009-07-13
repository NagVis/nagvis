import com.google.maps.LatLng;
import com.google.maps.controls.ZoomControl;
import com.google.maps.overlays.Polyline;

import flash.events.Event;
import flash.system.Security;

import modules.gmap.Link;
import modules.gmap.Location;
import modules.gmap.LocationsCollection;
import modules.gmap.LocationsView;
import modules.gmap.LocationsViewEvent;
import modules.gmap.Viewpoint;

import mx.collections.ArrayCollection;
import mx.controls.Alert;
import mx.events.ListEvent;
import mx.rpc.events.FaultEvent;
import mx.rpc.events.ResultEvent;

/*********************************************/
/* Global objects
/*********************************************/

private var viewpoints     : ArrayCollection;
private var locations      : LocationsCollection = new LocationsCollection;
private var foundLocations : LocationsCollection = new LocationsCollection;
private var links          : ArrayCollection;
private var hosts          : ArrayCollection;
private var services       : ArrayCollection = new ArrayCollection();

private var locationsView      : LocationsView;
private var foundLocationsView : FoundLocationsView;

/*********************************************/
/* Initialization
/*********************************************/

private function init() : void
{
	Security.allowInsecureDomain("*");
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

	rDatabase.getHosts();
	rDatabase.getServices();
	rViewpoints.getAll();
	rLocations.getAll();
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
	linksBox.services.dataProvider = services;

	locationsView.showLocations();

	rLinks.getAll();
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

	locationBox.locHosts.dataProvider = hosts;
}

private function getServices_handler(event : ResultEvent) : void
{
	var result : ArrayCollection = new ArrayCollection(event.result as Array);

	services = new ArrayCollection();
	for each (var service : Service in result)
		services.addItem(service);

	linksBox.services.dataProvider = services;
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
	rLocations.find(address);
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

	for each (var host : Host in hosts)
next_host:
		for each (var location : Location in locations)
			for each (var locationhost : Array in location.hosts)
				if (locationhost.id == host.id)
				{
					host.selected = true;
					break next_host;
				}

	if (locationsView.selectedLocation)
		locationBox.update(locationsView.selectedLocation);
	else
		locationBox.update(foundLocationsView.selectedLocation);
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
		rLocations.edit(locationBox.locID,
			(new LatLng(parseFloat(locationBox.locLat.text), parseFloat(locationBox.locLng.text)).toUrlValue(16)),
			locationBox.locName.text, locationBox.locAddress.text, locationBox.locDescription.text);
	}
	else
	{
		rLocations.add((new LatLng(parseFloat(locationBox.locLat.text), parseFloat(locationBox.locLng.text)).toUrlValue(16)),
			locationBox.locName.text, locationBox.locAddress.text, locationBox.locDescription.text);
	}
}

private function onDeleteLocation() : void
{
	if (locationBox.locID != "")
		rLocations.remove(locationBox.locID);
}

/*********************************************/
/* Link dialog box
/*********************************************/

private function onShowLinkBox() : void
{
	for each (var service : Service in services)
next_service:
		for each (var link : Link in links)
			for each (var linkservice : Array in link.services)
				if (linkservice.id == service.id)
				{
					service.selected = true;
					break next_service;
				}
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
		rLinks.add(location1.id, location2.id);
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
	rViewpoints.add(name, map.getCenter().toUrlValue(16), map.getZoom());
}

private function onSelectViewpoint(event : ListEvent) : void
{
	var viewpoint : Viewpoint = viewpointBox.list.selectedItem as Viewpoint;
	map.setCenter(LatLng.fromUrlValue(viewpoint.center));
	map.setZoom(viewpoint.zoom);
}
