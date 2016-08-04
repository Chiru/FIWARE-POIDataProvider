How to import POIs from OpenStreetMap data

NOTE: In database table names you cannot use a minus sign "-" even if 
      sub-region name (and OpenStreetMap filename has it. You have to use 
      underscore "_" instead. E.g. for sub region mecklenburg-vorpommern you
      must use mecklenburg_vorpommern in table names.
      

0. Optional things if needed

0.1 Install the osm2pgsql, if needed.

      $ sudo apt-get install osm2pgsql
      
0.2 For big datasets replicate the hierarchy of http://download.geofabrik.de/

    If you are going to import large areas, it might be easier to keep things in shape, when you replicate the structure of
    http://download.geofabrik.de/ by the directory hierarchy under 
    FIWARE-POIDataProvider/tools/poi_osm_import. Also, copy the import script template insert_fw_core_from_osm.sql to all directories. Copy the style
    also, but not to the lowest level.
    
    E.g.
      $ cd FIWARE-POIDataProvider/tools/poi_osm_import
      $ mkdir europe
      $ cd europe
      $ cp ../insert_fw_core_from_osm.sql .
      $ cp ../fiware_poi.style .
      $ mkdir germany
      $ cd germany
      $ cp ../insert_fw_core_from_osm.sql .
      $ cp ../fiware_poi.style .
      $ mkdir bayern
      $ cd bayern
      $ cp ../insert_fw_core_from_osm.sql .
      
      Then you are ready to import the sub-area data:
      
      $ wget http://download.geofabrik.de/europe/germany/bayern-latest.osm.pbf 

      
1. Download OpenStreetMap dataset from http://download.geofabrik.de/

    Browse for the area you want to download from 
    http://download.geofabrik.de/ .
    
    In the following templates sub-area and areaname are placeholders for 
    actual names e.g. "europe" and "finland". Replace them with the names of 
    the actual area you are downloading. For the download link right click the 
    [.osm.pbf] link at the download page to copy the link. Replace the parameter
    of wget with the link.
    
    For non-extensive non-hierarchical import start directly under the
    poi_osm_import level
      $ cd FIWARE-POIDataProvider/tools/poi_osm_import
    For hierarchical directory organization (see 0.2) you start with the
    proper level.
      $ cd sub-area (whatever it is)
    And then create the area and import:
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
          
    NOTE: Example where sub region name has a minus sign changed to underscore:
      $ osm2pgsql -U gisuser -S ../fiware_poi.style -d poidatabase -p mecklenburg_vorpommern_osm  mecklenburg-vorpommern-latest.osm.pbf
    
3. Customize the import sql script

    Make a custom copy

      $ cp ../insert_fw_core_from_osm.sql .

    Edit the SQL file before using it:
      * In ALL the SQL command replace all "areaname" (sub)strings with the 
        actual area name e.g. "finland" you used in phase 2.
      * modify the timestamp (at line 3) to match current times. You may use 
        e.g. http://www.currenttimestamp.com to obtain it.

4. Import the imported OSM data into FIWARE POI Data Provider database tables
    using the provided SQL file (insert_fw_core_from_osm.sql).
    
      $ psql -U gisuser -d poidatabase -f insert_fw_core_from_osm.sql
    
        
    