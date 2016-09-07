#
# Cookbook Name:: poi_dp
# Recipe:: install
#
# Copyright 2016, Adminotech Oy
#
# All rights reserved - Do Not Redistribute
#

#This chef recipe supports only Ubuntu version 14.04
if platform?("ubuntu") and node[:platform_version] == "14.04"
	log "Ubuntu 14.04 detected, installing POI data provider..."
	
	execute "apt-get update" do  
		action :run
	end
	
	package "postgis" do
    options '-y'
	  action :install
	end

	package "postgresql-9.3-postgis-2.1" do
    options '-y'
	  action :install
	end

	package "mongodb" do
	  action :install
	end

	package "apache2" do
	  action :install
	end

	package "php5" do
	  action :install
	end

	package "php5-pgsql" do
	  action :install
	end

	package "git" do
		action :install
	end

	package "php-pear" do
		action :install
	end

	package "php5-dev" do
		action :install
	end

	package "gcc" do
		action :install
	end

	package "make" do
		action :install
	end
	
  package "libcurl3-openssl-dev" do
		action :install
	end

  
	bash "install_and_configure_software" do
		user "ubuntu"
		cwd "/tmp"
		code <<-EOT
			export HOME=/root
			sudo pecl install mongo
			sudo pecl install pecl_http-1.7.6
      
      # Edit php.ini
			sudo cp --backup=numbered /etc/php5/apache2/php.ini /etc/php5/apache2/php.ini.backup_by_poi_dp
      sudo sed -i '/default extension directory/a\\; Extensions set for POI GE\\n\\nextension=mongo.so\\nextension=raphf.so\\nextension=propro.so\\nextension=http.so\\n' /etc/php5/apache2/php.ini

      # Edit apache2.conf
			sudo cp --backup=numbered /etc/apache2/apache2.conf /etc/apache2/apache2.conf.backup_by_poi_dp
			sudo sed -i '\\#<Directory \/var\/www\/>#,\\#<\/Directory># s|\\(AllowOverride\\) None|\\1 All|' /etc/apache2/apache2.conf
      
			sudo /etc/init.d/apache2 restart
			# Configuring PostGIS
			sudo -u postgres createuser gisuser
			sudo -u postgres createdb --encoding=UTF8 --owner=gisuser poidatabase
			sudo -u postgres psql -d poidatabase -f /usr/share/postgresql/9.3/contrib/postgis-2.1/postgis.sql
			sudo -u postgres psql -d poidatabase -f /usr/share/postgresql/9.3/contrib/postgis-2.1/spatial_ref_sys.sql
			sudo -u postgres psql -d poidatabase -f /usr/share/postgresql/9.3/contrib/postgis-2.1/postgis_comments.sql
			sudo -u postgres psql -d poidatabase -c "GRANT SELECT ON spatial_ref_sys TO PUBLIC;"
			sudo -u postgres psql -d poidatabase -c "GRANT ALL ON geometry_columns TO gisuser;"
			sudo -u postgres psql -d poidatabase -c 'create extension "uuid-ossp";'
			sudo cp --backup=numbered /etc/postgresql/9.3/main/pg_hba.conf /etc/postgresql/9.3/main/pg_hba.conf.backup_by_poi_dp
			sudo sed -i 's/local   all             all                                     peer/local   all             all                                     trust/g' /etc/postgresql/9.3/main/pg_hba.conf
			sudo /etc/init.d/postgresql restart
			# Installing POI Data Provider
			git clone https://github.com/Chiru/FIWARE-POIDataProvider.git
			cd FIWARE-POIDataProvider/install_scripts
			./create_tables.sh
			cd ../..
			sudo cp -r FIWARE-POIDataProvider/php /var/www/html/poi_dp
			wget http://getcomposer.org/composer.phar
			php composer.phar require justinrainbow/json-schema:1.4.3
			sudo cp -r vendor /var/www/html/poi_dp/
			# Enable Handling of Cross-origin Resource Sharing and URL Rewrite in Apache 
			sudo a2enmod headers
			sudo a2enmod rewrite
			sudo service apache2 restart
			# Set initial site identity configuration
			cd FIWARE-POIDataProvider
			sudo cp php/authenticate_t.html /var/www/html/poi_dp/authenticate.html
			sudo cp php/auth_conf_open.json /var/www/html/poi_dp/auth_conf.json
			suco cp chef/site_info.json /var/www/html/poi_dp/
			# Install the demo client
			sudo cp -r poi_mapper_client /var/www/html/pois
		EOT
	end

else 
	Chef::Application.fatal!("This chef recipe for installing the POI data provider supports only Ubuntu 14.04!")
end
