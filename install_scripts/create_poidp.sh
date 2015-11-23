#!/bin/bash -ex
# this works on Ubuntu 14.04
# As a guideline to do an unattended installation of Ubuntu, see 
# https://help.ubuntu.com/lts/installation-guide/i386/ch04s06.html
# The script is aborted if any command fails. If it is OK that a comand fails,
# use ./mycomand || true

# OS update
export DEBIAN_FRONTEND=noninteractive
sudo apt-get update -q

# Installing required packages
sudo apt-get install -y postgis postgresql-9.3-postgis-2.1 -q
sudo apt-get install mongodb -q -y
sudo apt-get install apache2 -q -y
sudo apt-get install php5 php5-pgsql -q -y
sudo apt-get install git -q -y

# The installation of the MongoDB module for PHP5
sudo apt-get install php-pear php5-dev gcc make -q -y
yes '' | sudo pecl install -f mongo

# Edit php.ini
sudo cp --backup=numbered /etc/php5/apache2/php.ini /etc/php5/apache2/php.ini.backup_by_poi_dp
sudo sed -i '/default extension directory/ a\; Extensions set for POI GE\n\nextension=mongo.so\nextension=raphf.so\nextension=propro.so\nextension=http.so\n' /etc/php5/apache2/php.ini

# Edit apache2.conf
sudo cp --backup=numbered /etc/apache2/apache2.conf /etc/apache2/apache2.conf.backup_by_poi_dp
sudo sed -i '\#<Directory \/var\/www\/>#,\#<\/Directory># s|\(AllowOverride\) None|\1 All|' /etc/apache2/apache2.conf

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