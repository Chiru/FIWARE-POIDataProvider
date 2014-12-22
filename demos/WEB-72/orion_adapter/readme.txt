orion_adapter

This program reads data from a vehicle_poi_server and feeds it to an Orion
Context Broker instance. The program is executed periodically as a cron-job.
The frequency of execution is set to once every 2 minutes, which is about the
best possible with the actual number of vehicles.

When installed, update the files as described below.

crontab.txt
  Unix crontab job for periodic execution of this program.
  On installation, the following fields may need editing:
    - the first number is the interval between executions in minutes
    - the web address must point to the file write_orion.php
    - verify the web address with a web browser
      - you should get a blank page, not any error message
      - then read the file status.txt (with web browser or otherwise)
        - should contain date and time of last execution and nothing else
        - if there is other content, check the other files
pois.txt
  List of POI's currently known to be stored into context broker, initially
  an empty list.
  The file must be writable (on Unix, use chmod a+w pois.txt).
  Verify that the list is not empty after execution of write_orion.php.
readme.txt
  This file, for information only.
status.txt
  Time of last execution of write_orion.php.
  This file must be writable (on Unix, use chmod a+w status.txt).
  Any additional information indicates failure on last execution.
write_orion.php
  The program to be executed periodically. In addition, can be executed from
  a web browser (e.g., for testing purposes). Updates the files pois.txt and
  status.txt. Also updates the POI's in the context broker.
  On installation, the following lines (near the beginnig) may need editing:
    define("restBaseURL", "http://orion.lab.fi-ware.org:1026/v1/");
      the last string must point to the Orion Context Broker instance
    define("POI_SERVER", "http://localhost/FI-WARE/demos/WEB-72/vehicle_poi_server/radial_search");
      the last string must point to the vehicle_poi_server's radial_search
