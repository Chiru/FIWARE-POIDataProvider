How to import POIs from OpenStreetMap data

1. Download OpenStreetMap dataset from http://download.geofabrik.de/
2. Import the dataset into PostGIS database with osm2pgsql:
    osm2pgsql -S fiware_poi.style -d poidatabase -p areaname_osm downloaded_dataset_filename.bz2
    
    The file path given to -S parameter must point to the fiware_poi.style file provided in this directory.
    Replace areaname in the -p parameter value with the name of the geographical area you are importing e.g. finland or bavaria. 
    The imported data will be stored in tables named based on this value, e.g. finland_osm_point etc.
    
3. Import the imported OSM data into FIWARE POI Data Provider database tables using the provided SQL file (insert_fw_core_from_osm.sql).
    psql -U gisuser -d poidatabase -f insert_fw_core_from_osm.sql
    
    You must modify the SQL file before using it:
        * replace the table name where the OSM data is stored with the one you used in phase 2 (e.g. finland_osm_point) in ALL the SQL commands
        * modify the timestamp to match current times
        
    