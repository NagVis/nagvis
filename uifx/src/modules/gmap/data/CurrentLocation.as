package modules.gmap.data
{
	import modules.gmap.domain.Location;

	public class CurrentLocation
	{
		[Bindable] public var location : Location;

		public function update(location : Location) : void
		{
			if (this.location !== location)
				this.location = location;
		}
	}
}
