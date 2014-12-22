vehicle_poi_server

This server reads public vehicle (bus, tram, etc.) data of Helsinki and Tampere
and presents them as POI data.

This is a partial implementation of POI server specification. Only radial_search
and bbox_search are implemented. There is no way to create, modify, or delete
POI's through this interface. The query paramters are optional. If none are
specified, all vehicles are returned.

bbox_search.php
radial_search.php
  The search functions.
  Each file has a line
    $next = $oldt + 10;
  The last number indicates (in seconds) how often data source servers can be
  accessed. The number should be at least 10.

HSL_siri.txt
TRE_siri.txt
  The data of Helsinki and Tampere as it was last read.
  These files must be writable (in Unix, use chmod a+w *_siri.txt).

In order to verify the installation, access the .php files with a web browser.
More than a screenful of data should be displayed.
