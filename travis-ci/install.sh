#!/bin/bash
#
# Setup the the box. This runs as root

apt-get -y update

# MySQL default username and password is "root"
echo "mysql-server-5.5 mysql-server/root_password password root" | debconf-set-selections
echo "mysql-server-5.5 mysql-server/root_password_again password root" | debconf-set-selections

apt-get -y install python-software-properties perl curl zip vim

# Add Java, php5.4, and Elasticsearch repos
add-apt-repository ppa:ondrej/php5-oldstable
add-apt-repository ppa:webupd8team/java -y
wget -qO - https://packages.elastic.co/GPG-KEY-elasticsearch | apt-key add -
echo "deb http://packages.elastic.co/elasticsearch/1.4/debian stable main" | tee -a /etc/apt/sources.list
apt-get -y update

# Install Apache+php54 stack
apt-get -y install mysql-server php5-mysql php5-curl php5-gd php5-imap libphp-pclzip php-apc php5 apache2 php5-curl php5-dev php5-xdebug

#Install Elasticsearch and Java

# Auto-accept oracle license
echo debconf shared/accepted-oracle-license-v1-1 select true | debconf-set-selections
# Install Java 8, elasticsearch 1.4, then run it as a service
apt-get -y install oracle-java8-installer
apt-get -y install elasticsearch

# Set up Elasticsearch to run as a service
echo "Setting up Elasticsearch as a service"
update-rc.d elasticsearch defaults 95 10

# Update apache2 php.ini
sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php5/apache2/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/' /etc/php5/apache2/php.ini
sed -i 's/;date.timezone =/date.timezone = UTC/' /etc/php5/apache2/php.ini

# Update cli php.ini for cron
sed -i 's/;date.timezone =/date.timezone = UTC/' /etc/php5/cli/php.ini

#!/bin/bash

# Add phpinfo to web dir as a convenience
echo "<?php phpinfo();"  > /var/www/phpinfo.php

# We will have Apache run as Vagrant user because this will help prevent permissions issues on a dev setup
sed -i "s/export APACHE_RUN_USER=www-data/export APACHE_RUN_USER=vagrant/" /etc/apache2/envvars
chown -R 777 /var/www/
#usermod -a -G www-data vagrant

# Enable some important Apache modules
a2enmod headers expires deflate rewrite

# Enable DEFLATE on application/json - this helps speed up downloads of Sugar data
cat >> /etc/apache2/mods-enabled/deflate.conf <<DELIM
<IfModule mod_deflate.c>
	AddOutputFilterByType DEFLATE application/json
</IfModule>
DELIM

# Update Apache config on web server to AllowOverride
sed -i "s/AllowOverride None/AllowOverride All/" /etc/apache2/sites-enabled/000-default

# Set up port 8080 virtualhost config to allow interactive installs of Sugar from Host system when using Vagrant
# Also make sure sugar directory has AllowOverride enabled
cat >> /etc/apache2/sites-available/sugar <<DELIM
Listen 8080
<VirtualHost *:8080>
	ServerName localhost
	DocumentRoot /var/www
</VirtualHost>
<Directory "/var/www/sugar">
	Options Indexes FollowSymLinks MultiViews
	AllowOverride All
	Order allow,deny
	Allow from all
</Directory>
DELIM
a2ensite sugar

# Restart apache22 when done
apachectl restart
