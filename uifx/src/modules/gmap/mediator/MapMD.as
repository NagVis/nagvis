package modules.gmap.mediator
{
	import com.google.maps.LatLng;
	import com.google.maps.controls.ZoomControl;
	
	import flash.events.IEventDispatcher;
	
	import modules.gmap.domain.Viewpoint;
	import modules.gmap.view.controls.GMapControl;

	public class MapMD
	{
		private var view : GMapControl;
		private var dispatcher : IEventDispatcher;
		
		public function MapMD(view : GMapControl, dispatcher : IEventDispatcher)
		{
			this.view = view;
			this.dispatcher = dispatcher;
		}
		
		public function showMap(key : String) : void
		{
			view.createMap(key);
		}
				
		public function initMap():void
		{
		  	view.map.enableScrollWheelZoom();
			view.map.enableContinuousZoom();
			view.map.addControl(new ZoomControl());
			view.map.setZoom(2);			
		}
		
		public function focusViewpoint(where : Viewpoint):void
		{
			view.map.setCenter(LatLng.fromUrlValue(where.center));
			view.map.setZoom(where.zoom);
		}
		
		public function extractViewpoint(name : String):Viewpoint
		{
			var vp : Viewpoint = new Viewpoint;
			vp.label = name;
			vp.center = view.map.getCenter().toUrlValue(16);
			vp.zoom = view.map.getZoom();
			
			return vp;
		}
	}
}