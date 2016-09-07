#
# Cookbook Name:: poi_dp
# Recipe:: start
#
# Copyright 2016, Adminotech Oy
#
# All rights reserved - Do Not Redistribute
#

#This chef recipe supports only Ubuntu version 14.04
if platform?("ubuntu") and node[:platform_version] == "14.04"
	log "Ubuntu 14.04 detected, starting POI data provider..."
	
	service "apache2" do
		action :start
	end
	
else 
	Chef::Application.fatal!("This chef recipe for starting the POI data provider supports only Ubuntu 14.04!")
end
