#!/bin/bash -ex

# The script is aborted if any command fails. If it is OK that a comand fails,
# use ./mycomand || true
curl "http://$IP/poi_dp/radial_search?lat=1&lon=1&category=test_poi" | grep "Test POI 1"