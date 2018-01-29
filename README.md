# BOF
Birds of a feather flock together: this is a software that can be used for proposing BOF topics and for voting on them

# Installation

on Ubuntu 14.04, you will need these packages:

    apt install apache2 libapache2-mod-php7.0 php php-mysql mariadb-server php-zip git vim ca-certificates composer php-dom php-mbstring

get the dependancies with composer:

    cd src
    composer install

setup the logs directory:

    mkdir src/logs
    chmod a+w src/logs

configure the database connection:

    cp cfg/settings-example.php cfg/settings.php

load the database:

    mysql -u myuser mydbname -p < sql/createtables.sql
    mysql -u myuser mydbname -p < sql/initialdata.sql
