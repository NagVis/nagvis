package modules.gmap.data
{
	import modules.gmap.domain.Viewpoint;

	import mx.collections.ArrayCollection;

	public class ViewpointsData extends ArrayCollection
	{
		public function ViewpointsData(source : Array = null)
		{
			super(source);
		}

		public function fill(data : Array) : void
		{
			this.source = data;
		}

	}
}
