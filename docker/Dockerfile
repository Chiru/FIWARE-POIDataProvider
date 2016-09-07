FROM ubuntu:14.04
MAINTAINER FIWARE Adminotech

# Install dependencies
RUN apt-get update && apt-get install -y \
  mongodb \
  apache2 \
  php5 \
  php5-pgsql \
  git \
  php-pear \
  php5-dev \
  gcc \
  make \
  libcurl3-openssl-dev \
  wget

# Install mongo and pecl
RUN printf "\n" | pecl install mongo && \
    printf "\n" | pecl install pecl_http-1.7.6

# Edit php.ini
RUN cp --backup=numbered /etc/php5/apache2/php.ini /etc/php5/apache2/php.ini.backup_by_poi_dp && \
    sed -i '/default extension directory/a\; Extensions set for POI GE\n\nextension=mongo.so\nextension=raphf.so\nextension=propro.so\nextension=http.so\n' /etc/php5/apache2/php.ini

# Edit apache2.conf
RUN cp --backup=numbered /etc/apache2/apache2.conf /etc/apache2/apache2.conf.backup_by_poi_dp && \
    sed -i '\#<Directory \/var\/www\/>#,\#<\/Directory># s|\(AllowOverride\) None|\1 All|' /etc/apache2/apache2.conf

# Configuring PostGIS & Installing POI Data Provider
RUN locale-gen --no-purge en_US.UTF-8
ENV LC_ALL en_US.UTF-8
RUN update-locale LANG=en_US.UTF-8
RUN apt-get update && apt-get install -y \
  postgis \
  postgresql-9.3-postgis-2.1
RUN service postgresql start && \
    service apache2 start && \
    service mongodb start && \
    sudo -u postgres createuser gisuser && \
    sudo -u postgres createdb --encoding=UTF8 --owner=gisuser poidatabase && \
    sudo -u postgres psql -d poidatabase -f /usr/share/postgresql/9.3/contrib/postgis-2.1/postgis.sql && \
    sudo -u postgres psql -d poidatabase -f /usr/share/postgresql/9.3/contrib/postgis-2.1/spatial_ref_sys.sql && \
    sudo -u postgres psql -d poidatabase -f /usr/share/postgresql/9.3/contrib/postgis-2.1/postgis_comments.sql && \
    sudo -u postgres psql -d poidatabase -c "GRANT SELECT ON spatial_ref_sys TO PUBLIC;" && \
    sudo -u postgres psql -d poidatabase -c "GRANT ALL ON geometry_columns TO gisuser;" && \
    sudo -u postgres psql -d poidatabase -c 'create extension "uuid-ossp";' && \
    cp --backup=numbered /etc/postgresql/9.3/main/pg_hba.conf /etc/postgresql/9.3/main/pg_hba.conf.backup_by_poi_dp && \
		sed -i 's/local   all             all                                     peer/local   all             all                                     trust/g' /etc/postgresql/9.3/main/pg_hba.conf && \
    /etc/init.d/postgresql restart && \
    git clone https://github.com/Chiru/FIWARE-POIDataProvider.git && \
    cd FIWARE-POIDataProvider/install_scripts && \
    ./create_tables.sh && \
    cd ../.. && \
    sudo cp -r FIWARE-POIDataProvider/php /var/www/html/poi_dp && \
    wget http://getcomposer.org/composer.phar && \
    php composer.phar require justinrainbow/json-schema:1.4.3 && \
    cp -r vendor /var/www/html/poi_dp/
    
# Enable Handling of Cross-origin Resource Sharing and URL Rewrite in Apache 
RUN service apache2 start && \
    a2enmod headers && \
    a2enmod rewrite && \
    service apache2 stop

# Set initial site identity configuration
RUN cd FIWARE-POIDataProvider && \
    cp php/authenticate_t.html /var/www/html/poi_dp/authenticate.html && \
    cp php/auth_conf_open.json /var/www/html/poi_dp/auth_conf.json && \
    cp docker/site_info.json /var/www/html/poi_dp/
    
# Install the demo client
RUN sudo cp -r poi_mapper_client /var/www/html/pois

EXPOSE 80

CMD service postgresql start && service mongodb start && /usr/sbin/apache2ctl -D FOREGROUND