How to import POIs from OpenStreetMap data

0. Install the osm2pgsql, if needed.

      $ sudo apt-get install osm2pgsql
      
1. Download OpenStreetMap dataset from http://download.geofabrik.de/

    Browse for the area you want to download from 
    http://download.geofabrik.de/ .
    
    In the following template sub-region and areaname are placeholders for 
    actual names e.g. "europe" and "finland". Replace them with the names of 
    the actual area you are downloading. If you download a big sub region, e.g.
    europe, in one file, you'll use sub-region name later as areaname.

      $ cd FIWARE-POIDataProvider/tools/poi_osm_import
      $ mkdir areaname
      $ cd areaname
      $ wget http://download.geofabrik.de/sub-region/areaname-latest.osm.pbf


2. Import the dataset into PostGIS database with osm2pgsql:
    
      $ osm2pgsql -U gisuser -S ../fiware_poi.style -d poidatabase -p areaname_osm  areaname-latest.osm.pbf
    
    The file path given to -S parameter must point to the fiware_poi.style file 
    provided in this directory.
    Replace areaname in the -p parameter value with the name of the geographical
    area you are importing e.g. bavaria. The last parameter is
    the name of the data file you just downloaded.
    The imported data will be stored in tables named based on this value, e.g. 
    finland_osm_point etc.
    
    NOTE: In case of Killed termination, you may try the command with --slim
          option. It runs longer but uses less memory.
    
3. Customize the import sql script

    Make a custom copy

      $ cp ../insert_fw_core_from_osm.sql insert_fw_core_from_osm.sql

    Edit the SQL file before using it:
      * In ALL the SQL command replace all "areaname" (sub)strings with the 
        actual area name e.g. "finland" you used in phase 2.
      * modify the timestamp (at line 3) to match current times. You may use 
        e.g. http://www.currenttimestamp.com to obtain it.

4. Import the imported OSM data into FIWARE POI Data Provider database tables
    using the provided SQL file (insert_fw_core_from_osm.sql).
    
      $ psql -U gisuser -d poidatabase -f insert_fw_core_from_osm.sql
    
        
    