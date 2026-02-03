# Deployment Note

* For now, Git needs to include .env file with production db connection data
* Post deployment (upon success), 
    * cd /includes/phpGrid
    * composer install --ignore-platform-req=ext-gd 
    * npm install
* OC is PHP 8.4 running great, localhost on 8.4 seems very slow