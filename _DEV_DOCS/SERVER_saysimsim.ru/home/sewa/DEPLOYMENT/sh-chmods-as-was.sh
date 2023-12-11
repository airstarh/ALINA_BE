#!/bin/sh
##################################################
#region RIGHTS
# CHOWN
sudo chown www-data:www-data /srv/ -R
sudo chmod -R 755 /srv/
#####
sudo chown www-data:www-data /var/www/ -R
sudo chmod -R 755 /var/www/
#endregion RIGHTS
##################################################
