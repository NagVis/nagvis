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

package modules.gmap.view
{
	import flash.events.MouseEvent;
	
	import lib.ui.EdgeBox;
	
	import mx.controls.Image;
	import mx.events.FlexEvent;

	public class Dialog extends EdgeBox
	{
		public var logoImage : Image = new Image();
		public var closeImage : Image = new Image();

		[Bindable] [Embed(source="modules/gmap/img/right1.gif")]
		private var closeClass : Class;

		private function showOnClick(event : *) : void
		{
			setCurrentState("right-expanded");
		}

		private function hideOnClick(event : *) : void
		{
			setCurrentState("right-contracted");
		}

		private function addImages(event : *) : void
		{
			logoImage.setStyle("top", 5);
			logoImage.setStyle("left", 5);
			logoImage.width=30;
			logoImage.height=30;
			logoImage.scaleContent = true;
			logoImage.addEventListener(MouseEvent.CLICK, showOnClick);
			this.addChild(logoImage);

			closeImage.setStyle("top", 10);
			closeImage.setStyle("right", 20);
			closeImage.width = 20;
			closeImage.height = 20;
			closeImage.scaleContent = true;
			closeImage.source = new closeClass();
			closeImage.addEventListener(MouseEvent.CLICK, hideOnClick);
			this.addChild(closeImage);
		}

		public function Dialog()
		{
			super();

			this.addEventListener(FlexEvent.CREATION_COMPLETE, addImages);
		}

		public function set logo(value:Class) : void
		{
			logoImage.source = new value();
		}
	}
}