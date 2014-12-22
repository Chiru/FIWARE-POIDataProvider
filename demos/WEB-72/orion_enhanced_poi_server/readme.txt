orion_enhanced_poi_server
  This is an otherwise normal POI server that in addition reads vehicle POI's
  from Orion Context Broker. Editing functionality is restricted to the normal
  POI database that is not connected to the Context Broker.
  
The file cb.php contains the line
    $base_addr = 'http://orion.lab.fi-ware.org:1026/v1/queryContext';
The string must point to an instance of the Orion Context Broker and its
queryContext operation.

If the context broker instance in use requres an authentication key, that key
must be in the file ../orion_key.txt.
In the file there must be the character pair [" before the key and "] after the key.

Installation can be verified by reading radial_search.php with a web browser.
Parameters must be specified, e.g.,
    http://localhost/FI-WARE/demos/WEB-72/orion_enhanced_poi_server/radial_search?lat=60.170912&lon=24.941515&radius=10000

