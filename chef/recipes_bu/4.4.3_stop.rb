#
# Cookbook Name:: poi_dp
# Recipe:: stop
#
# Copyright 2014, CIE, University of Oulu
#
# All rights reserved - Do Not Redistribute
#

#This chef recipe supports only Ubuntu version 14.04
if platform?("ubuntu") and node[:platform_version] == "14.04"
	log "Ubuntu 14.04 detected, stopping POI data provider..."
	
	service "apache2" do
		action :stop
	end
	
else 
	Chef::Application.fatal!("This chef recipe for stopping the POI data provider supports only Ubuntu 14.04!")
end
