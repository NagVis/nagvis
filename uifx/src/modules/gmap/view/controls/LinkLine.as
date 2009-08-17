package modules.gmap.view.controls
{
	import com.google.maps.LatLng;
	import com.google.maps.overlays.Polyline;
	
	import modules.gmap.domain.Link;

	public class LinkLine extends Polyline
	{
		public function LinkLine(link:Link)
		{
			var point1 : LatLng = LatLng.fromUrlValue(link.location1.point);
			var point2 : LatLng = LatLng.fromUrlValue(link.location2.point);
			super([point1, point2]);
		}
		
	}
}