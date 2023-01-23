#!/bin/sh
##################################################
# https://serverfault.com/questions/357108/what-permissions-should-my-website-files-folders-have-on-a-linux-webserver?newreg=39fc9a6627a248cea5726ab197bcab22
##################################################
chown -R sewa /var/www/
chgrp -R www-data /var/www/
chmod -R 750 /var/www/
chmod -R g+s /var/www/
##################################################
chmod -R g+w /var/www/saysimsim.ru/uploads/
chmod -R g+w /var/www/vov/uploads/
chmod -R g+w /var/www/m45a/uploads/
##################################################
chown -R sewa /srv/alina/
chgrp -R www-data /srv/alina/
chmod -R 750 /srv/alina/
chmod -R g+s /srv/alina/
##################################################
chown -R sewa /home/sewa/DEPLOYMENT/
chgrp -R www-data /home/sewa/DEPLOYMENT/
chmod -R 750 /home/sewa/DEPLOYMENT/
chmod -R g+s /home/sewa/DEPLOYMENT/
##################################################
