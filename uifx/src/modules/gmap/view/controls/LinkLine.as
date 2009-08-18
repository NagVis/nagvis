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

package modules.gmap.view.controls
{
	import com.google.maps.LatLng;
	import com.google.maps.overlays.Polyline;

	import modules.gmap.domain.Link;

	public class LinkLine extends Polyline
	{
		public function LinkLine(link : Link)
		{
			var point1 : LatLng = LatLng.fromUrlValue(link.location1.point);
			var point2 : LatLng = LatLng.fromUrlValue(link.location2.point);
			super([point1, point2]);
		}
	}
}
