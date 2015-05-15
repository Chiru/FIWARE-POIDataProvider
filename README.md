FIWARE-POIDataProvider
======================

POI Data Provider is a web service for provisioning Points of Interest (POI) 
data. It is a RESTful web service implemented with PHP.

Version information
-------------------
2015-05-15  Merged dynamic_pois branch to Master.
            New features
            * Dynamic POI information can be fetched from other REST services. 
              Service access and data extraction are configurable per service 
              type.
            * Dynamic POI information is cached until expired. Expiration time
              is configurable per POI.
