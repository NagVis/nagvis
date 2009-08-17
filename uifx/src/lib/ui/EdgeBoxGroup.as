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

package lib.ui
{
	public class EdgeBoxGroup
	{
		private var _components : Array = new Array();
		private var _current : EdgeBox;
		private var _playTransition : Boolean;

		public var busy : Boolean = false;

		public function get current() : EdgeBox
		{
			return _current;
		}

		public function register(component : EdgeBox) : void
		{
			_components.push(component);
			component.addEventListener("resized", onResized);
		}

		public function setCurrentBox(box : EdgeBox, playTransition : Boolean = true) : void
		{
			_playTransition = playTransition;

			if (_current != box)
			{
				if(_current)
				{
					_current.setCurrentStateInternal(_current.side + "-contracted", _playTransition);
					_current = box;
				}
				else
				{
					_current = box;
					_current.setCurrentStateInternal(_current.side + "-expanded", _playTransition);
				}

			}
		}

		private function onResized(event : * = null) : void
		{
			if (_current)
				_current.setCurrentStateInternal(_current.side + "-expanded", _playTransition);
		}
	}
}
