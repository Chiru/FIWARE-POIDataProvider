#!/bin/bash
psql -U gisuser -d poidatabase -a -f create_table_fw_core.sql
psql -U gisuser -d poidatabase -a -f create_table_fw_core_intl.sql
psql -U gisuser -d poidatabase -f test_data/insert_fw_core_test_pois.sql
mongoimport --db poi_db --collection fw_time --file test_data/example_poi_2_fw_time.json
mongoimport --db poi_db --collection fw_contact --file test_data/example_poi_2_fw_contact.json
mongoimport --db poi_db --collection fw_media --file test_data/example_poi_2_fw_media.json
mongoimport --db poi_db --collection fw_marker --file test_data/example_poi_2_fw_marker.json
mongoimport --db poi_db --collection fw_relationships --file test_data/example_poi_2_fw_relationships.json
mongoimport --db poi_db --collection fw_time --file test_data/example_poi_3_fw_time.json