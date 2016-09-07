#
# Cookbook Name:: poi_dp
# Recipe:: uninstall
#
# Copyright 2014, CIE, University of Oulu
#
# All rights reserved - Do Not Redistribute
#

#This chef recipe supports only Ubuntu version 14.04
if platform?("ubuntu") and node[:platform_version] == "14.04"
	log "Ubuntu 12.04 detected, uninstalling POI data provider..."

	bash "uninstall_and_configure_software" do
		user "root"
		cwd "/tmp"
		code <<-EOT
			pecl uninstall mongo
			rm -r /var/www/poi_dp
		EOT
	end
		
else 
	Chef::Application.fatal!("This chef recipe for uninstalling the POI data provider supports only Ubuntu 14.04!")
end
