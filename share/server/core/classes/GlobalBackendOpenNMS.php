<?php
/*****************************************************************************
 *
 * GlobalBackendOpenNMS.php - backend class for connecting NagVis to
 *                            OpenNMS via the REST API.
 *
 *****************************************************************************/

/**
 * @author Andreas Fuchs <andreas.fuche@nethinks.com>
 */


 class GlobalBackendOpenNMS implements GlobalBackendInterface {

    private $CORE;
    private $opennmsHost;
    private $restPort;
    private $restUser;
    private $restPassword;

   /**
    * Read the configuration and set up variables
    */
    public function __construct($backendId)
    {
      //$this->CORE = $CORE;
      $this->backendId = $backendId;

      $this->opennmsHost = cfg('backend_'.$backendId, 'opennmshost');
      $this->restPort = cfg('backend_'.$backendId, 'restport');
      $this->restUser = cfg('backend_'.$backendId, 'restuser');
      $this->restPassword = cfg('backend_'.$backendId, 'restpassword');
    }

    /**
     * Static function which returns the backend specific configuration options
     * and defines the default values for the options
     */
    public static function getValidConfig()
    {
        return Array (
        'opennmshost' => Array('must' => 1,
            'editable' => 1,
            'default' => 'localhost',
            'match' => MATCH_STRING_NO_SPACE),
        'restport' => Array('must' => 1,
            'editable' => 1,
            'default' => '8980',
            'match' => MATCH_INTEGER),
         'restuser' => Array('must' => 1,
            'editable' => 1,
            'default' => 'opennms',
            'match' => MATCH_STRING_NO_SPACE),
        'restpassword' => Array('must' => 1,
            'editable' => 1,
            'default' => 'opennms',
            'match' => MATCH_STRING_EMPTY)
        );
    }

    /**
     * Used in WUI forms to populate the object lists when adding or modifying
     * objects in WUI.
     */
    public function getObjects($type, $name1Pattern = '', $name2Pattern = '')
    {
      $output = Array();
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_USERPWD, "$this->restUser:$this->restPassword");
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      //return the transfer as a string
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      switch($type)
      {
        //object is a host
        case "host":

        // set url
        $url = "{$this->opennmsHost}:{$this->restPort}/opennms/rest/nodes?orderBy=label&limit=0";
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        $xmlresult = new SimpleXMLElement($result);

        foreach($xmlresult->node as $node)
        {
          //output with following pattern: nodelabel@nodeid
          $output[] = Array('name1' => "{$node['label']}@{$node['id']}", 'name2' => $node['id']);
        }
        break;

        //object is a service
        case "service":
        //get nodeid of current node
        $nodeId = substr($name1Pattern, strpos($name1Pattern, "@") + 1 );
        //get all services of the current node from OpenNMS
        $url="{$this->opennmsHost}:{$this->restPort}/opennms/rest/nodes/{$nodeId}/ipinterfaces?limit=0";
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        $xmlresult = new SimpleXMLElement($result);

        foreach($xmlresult->ipInterface as $ipInterface)
        {
          // getting services for the interface
          $url2="{$this->opennmsHost}:{$this->restPort}/opennms/rest/nodes/{$nodeId}/ipinterfaces/{$ipInterface->ipAddress}/services?limit=0";
          curl_setopt($ch, CURLOPT_URL, $url2);
          $result2 = curl_exec($ch);
          $xmlresult2 = new SimpleXMLElement($result2);

          foreach ($xmlresult2->service as $service)
          {
            //output with following pattern: servicename@IP
            $output[] = Array('name1' => "{$service->serviceType->name}@{$ipInterface->ipAddress}]", 'name2' => "{$service->serviceType->name}@{$ipInterface->ipAddress}");
            }
         }

         // close curl resource to free up system resources
         break;
      }
      // close curl resource to free up system resources
      curl_close($ch);
      return $output;
    }

    /**
     * Returns the state with detailed information of a list of hosts. Using the
     * given objects and filters.
     */
    public function getHostState($objects, $options, $filters)
    {
      $output = Array();
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
      curl_setopt($ch, CURLOPT_USERPWD, "$this->restUser:$this->restPassword");
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

      if(count($filters) == 1 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '=')
      {
        //walk through all objects of the collection
      foreach($objects as $object)
      {
        $objectName = $object[0]->getName();
        if(strpos($objectName, '@') == FALSE)
        {
          break;
        }
        //get nodeID
        $objectId = substr($objectName, strpos($objectName, "@") + 1 );
        //check, if node is in OpenNMS
        $url="{$this->opennmsHost}:{$this->restPort}/opennms/rest/nodes/{$objectId}";
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);

        // If no node is found the OpenNMS api responce "Node <nodeid> was not found."
        // Otherwise the responce is a xml file.
        $search = "Node";
        if (strncmp($result, $search, strlen($search)) === 0)
        {
          //return unknown
          $output[$objectName] = Array(ALIAS => $objectName,
                                       STATE => UNKNOWN,
                                       OUTPUT => "Object not found in OpenNMS",
                                       'display_name' => $objectName,
                                       'address' => $objectName,
                                       'notes' => "",
                                       'last_check' => 0,
                                       'next_check' => 0,
                                       'current_check_attempt' => 1,
                                       'max_check_attempts' => 1,
                                       'last_state_change' => 0,
                                       'last_hard_state_change' => 0,
                                       'statusmap_image' => "",
                                       'perfdata' => "",
                                       ACK => 0,
                                       DOWNTIME => 0);
        }
        else
        {
          // get all current outages of the node
          $url="{$this->opennmsHost}:{$this->restPort}/opennms/rest/outages/forNode/{$objectId}?limit=0";
          curl_setopt($ch, CURLOPT_URL, $url);
          $result = curl_exec($ch);
          $xmlresult = new SimpleXMLElement($result);
          // check if outages exists
          $count = 0;
          foreach ($xmlresult->children() as $child)
          {
            $count += 1;
          }

         //if there is an outage
         if($count > 0)
         {
           //return down
           $output[$objectName] = Array(ALIAS => $objectName,
                                        STATE => DOWN,
                                        OUTPUT => "Some or all services on this host are down",
                                        'display_name' => $objectName,
                                        'address' => $objectName,
                                        'notes' => "",
                                        'last_check' => 0,
                                        'next_check' => 0,
                                        'current_check_attempt' => 1,
                                        'max_check_attempts' => 1,
                                        'last_state_change' => 0,
                                        'last_hard_state_change' => 0,
                                        'statusmap_image' => "",
                                        'perfdata' => "",
                                        ACK => 0,
                                        DOWNTIME => 0);
         }
         else
         {
           //return UP
           $output[$objectName] = Array( ALIAS => $objectName,
                                         STATE => UP,
                                         OUTPUT => "All services of this host are up",
                                        'display_name' => $objectName,
                                        'address' => $objectName,
                                        'notes' => "",
                                        'last_check' => 0,
                                        'next_check' => 0,
                                        'current_check_attempt' => 1,
                                        'max_check_attempts' => 1,
                                        'last_state_change' => 0,
                                        'last_hard_state_change' => 0,
                                        'statusmap_image' => "",
                                        'perfdata' => "",
                                         ACK => 0,
                                         DOWNTIME => 0);
             }
           }
         }
       }
       $ch = curl_init();
       return $output;
    }

    /**
     * Returns the state with detailed information of a list of services. Using
     * the given objects and filters.
     */
    public function getServiceState($objects, $options, $filters)
    {
      $output = Array();
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
      curl_setopt($ch, CURLOPT_USERPWD, "$this->restUser:$this->restPassword");
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      if(count($filters) == 2 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '='
            && $filters[1]['key'] == 'service_description' && $filters[1]['op'] == '=')
      {
        //walk through all objects of the collection
       foreach($objects as $object)
       {
         $objectName = $object[0]->getName();
         //get nodeID
         $objectId = substr($objectName, strpos($objectName, "@") + 1 );
         $objectService = $object[0]->getServiceDescription();
           //get service name
           $objectServiceName = substr($objectService, 0, strpos($objectService, "@"));
           //get service IP
           $objectServiceIP = substr($objectService, strpos($objectService, "@") + 1 );
           //check if the node and service is in OpenNMS
           $url="{$this->opennmsHost}:{$this->restPort}/opennms/rest/nodes/{$objectId}/ipinterfaces/{$objectServiceIP}/services/{$objectServiceName}?limit=0";
           curl_setopt($ch, CURLOPT_URL, $url);
           $result = curl_exec($ch);

           // if node and service exists in OpenNMS the resonce is XML otherwise a String
           $search = "<";
           if (strncmp($result, $search, strlen($search)) === 0)
           {
             // check if outages exists
             $count = 0;
             $url="{$this->opennmsHost}:{$this->restPort}/opennms/rest/outages/forNode/{$objectId}?limit=0";
             curl_setopt($ch, CURLOPT_URL, $url);
             $result = curl_exec($ch);
             $xmlresult = new SimpleXMLElement($result);

             foreach ($xmlresult->outage as $outage)
             {
                //echo "{$outage->monitoredService->serviceType->name} <br/><hr/>";
                //if($outage->serviceType->name == 'ICMP' and $outage->ipAddress == '212.218.87.167')
                if($outage->monitoredService->serviceType->name == $objectServiceName and $outage->ipAddress == $objectServiceIP)
                {
                        $count += 1;
                }
             }

             //if there is an outage
             if($count > 0)
             {
               //return service DOWN
               $output[$objectName.'~~'.$objectService] =  Array('name' => $objectServiceName,
                                   'service_description' => $objectService,
                                   ALIAS => $objectServiceName,
                                   STATE => DOWN,
                                   STALE => 0,
                                   OUTPUT => "Service is down",
                                   'display_name' => $objectServiceName,
                                   'address' => $objectServiceName,
                                   'notes' => "",
                                   'last_check' => 0,
                                   'next_check' => 0,
                                   'current_check_attempt' => 1,
                                   'max_check_attempts' => 1,
                                   'last_state_change' => 0,
                                   'last_hard_state_change' => 0,
                                   'statusmap_image' => "",
                                   'perfdata' => "",
                                   ACK => 0,
                                   DOWNTIME => 0);
             }
             else
             {
               //return service UP
               $output[$objectName.'~~'.$objectService] =  Array('name' => $objectServiceName,
                                   'service_description' => $objectService,
                                   ALIAS => $objectServiceName,
                                   STATE => UP,
                                   STALE => 0,
                                   OUTPUT => "Service is up",
                                   'display_name' => $objectServiceName,
                                   'address' => $objectServiceName,
                                   'notes' => "",
                                   'last_check' => 0,
                                   'next_check' => 0,
                                   'current_check_attempt' => 1,
                                   'max_check_attempts' => 1,
                                   'last_state_change' => 0,
                                   'last_hard_state_change' => 0,
                                   'statusmap_image' => "",
                                   'perfdata' => "",
                                   ACK => 0,
                                   DOWNTIME => 0);
             }
           }
           else
           {
              //return service UNKNOWN
              $output[$objectName.'~~'.$objectService] =  Array('name' => $objectServiceName,
                                  'service_description' => $objectService,
                                  ALIAS => $objectServiceName,
                                  STATE => UNKNOWN,
                                  STALE => 0,
                                  OUTPUT => "Service not found in OpenNMS",
                                  'display_name' => $objectServiceName,
                                  'address' => $objectServiceName,
                                  'notes' => "",
                                  'last_check' => 0,
                                  'next_check' => 0,
                                  'current_check_attempt' => 1,
                                  'max_check_attempts' => 1,
                                  'last_state_change' => 0,
                                  'last_hard_state_change' => 0,
                                  'statusmap_image' => "",
                                  'perfdata' => "",
                                  ACK => 0,
                                  DOWNTIME => 0);
           }

        }
      }
      $ch = curl_init();
      return $output;
    }

    /**
     * Returns the service state counts for a list of hosts. Using
     * the given objects and filters.
     */
    public function getHostStateCounts($objects, $options, $filters)
    {
      //not implemented
      return Array();
    }

    /**
     * Returns the host and service state counts for a list of hostgroups. Using
     * the given objects and filters.
     */
    public function getHostgroupStateCounts($objects, $options, $filters)
    {
      //not implemented
      return Array();
    }

    /**
     * Returns the service state counts for a list of servicegroups. Using
     * the given objects and filters.
     */
    public function getServicegroupStateCounts($objects, $options, $filters)
    {
      //not implemented
      return Array();
    }

    /**
     * Returns a list of host names which have no parent defined.
     */
    public function getHostNamesWithNoParent()
    {
      //not implemented
      return Array();
    }

    /**
     * Returns a list of host names which are direct childs of the given host
     */
    public function getDirectChildNamesByHostName($hostName)
    {
      //not implemented
      return Array();
    }

    /**
     * Returns a list of host names which are direct parents of the given host
     */
    public function getDirectParentNamesByHostName($hostName)
    {
      //not implemented
      return Array();
    }

   /**
     * Returns the service state counts for a list of hosts. Using
     * the given objects and filters.
     */
    public function getHostMemberCounts($objects, $options, $filters) {
       return Array();
    }

 }
?>
