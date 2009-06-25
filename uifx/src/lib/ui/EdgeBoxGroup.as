package lib.ui
{
	public class EdgeBoxGroup
	{
		private var _components : Array = new Array();
		private var _current : EdgeBox;
		private var _playTransition : Boolean;
		
		public var busy : Boolean = false;
		
		public function register(component : EdgeBox):void
		{
			_components.push(component);
			component.addEventListener("resized", onResized);
		}
			
		public function setCurrentBox(box : EdgeBox, playTransition : Boolean = true):void
		{
			_playTransition = playTransition;
			
			if(_current != box)
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
		
		private function onResized(event : * = null):void
		{
				if(_current)
					_current.setCurrentStateInternal(_current.side + "-expanded", _playTransition);			
		}
	}
}