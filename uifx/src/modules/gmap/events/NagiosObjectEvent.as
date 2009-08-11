package modules.gmap.events
{
	import flash.events.Event;

	/***
	 * This class serves two purposes:
	 * 	-	it is the placeholder of different event type constants 
	 * 		that are used to relize the Nagios oblects laze load
	 * 	-	it's suffix param let us relize a nice hack to load
	 * 		these objects only once just by means of Mate event maps
	 * 		without any implication on data objects themselves
	 ***/

	public class NagiosObjectEvent extends Event
	{
		public static const NEED_ALL_HOSTS : String = "NagiosObjectNeedAllHosts";
		public static const NEED_ALL_HOSTGROUPS : String = "NagiosObjectNeedAllHostgroups";
		public static const NEED_ALL_SERVICES : String = "NagiosObjectNeedAllServices";
		public static const NEED_ALL_SERVICEGROUPS : String = "NagiosObjectNeedAllServicegroups";

		public static const LOAD_ALL_HOSTS : String = "NagiosObjectLoadAllHosts";
		public static const LOAD_ALL_HOSTS_ONCE : String = "NagiosObjectLoadAllHosts0";
		public static const LOAD_ALL_HOSTGROUPS : String = "NagiosObjectLoadAllHostgroups";
		public static const LOAD_ALL_HOSTGROUPS_ONCE : String = "NagiosObjectLoadAllHostgroups0";
		public static const LOAD_ALL_SERVICES : String = "NagiosObjectLoadAllServices";
		public static const LOAD_ALL_SERVICES_ONCE : String = "NagiosObjectLoadAllServices0";
		public static const LOAD_ALL_SERVICEGROUPS : String = "NagiosObjectLoadAllServicegroups";
		public static const LOAD_ALL_SERVICEGROUPS_ONCE : String = "NagiosObjectLoadAllServicegroups0";
		
		public function NagiosObjectEvent(type:String, suffix:String = '', bubbles:Boolean = true, cancelable:Boolean = false)
		{
			super(type + suffix, bubbles, cancelable);
		}
		
	}
}