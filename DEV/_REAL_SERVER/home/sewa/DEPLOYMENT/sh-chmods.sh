#!/bin/sh
##################################################
# https://serverfault.com/questions/357108/what-permissions-should-my-website-files-folders-have-on-a-linux-webserver?newreg=39fc9a6627a248cea5726ab197bcab22
##################################################
#region /srv/
 chown -R sewa /srv/
 chgrp -R www-data /srv/
 chmod -R 750 /srv/
 chmod g+s /srv/
#endregion /srv/
##################################################
#region /var/www/
 chown -R sewa /var/www/
 chgrp -R www-data /var/www/
 chmod -R 750 /var/www/
 chmod g+s /var/www/
#endregion /var/www/
##################################################
#region UPLOADS
 chmod -R 770 /var/www/saysimsim.ru/uploads
 chmod -R 770 /var/www/m45a/uploads
 chmod -R 770 /var/www/vov/uploads
 chmod -R 770 /var/www/osspb/uploads
#endregion UPLOADS
##################################################
#region HOME SEWA DEPLOYMENT
chown -R sewa /home/sewa/DEPLOYMENT/
chgrp -R www-data /home/sewa/DEPLOYMENT/
chmod -R 750 /home/sewa/DEPLOYMENT/
chmod -R u+x /home/sewa/DEPLOYMENT/
#endregion HOME SEWA DEPLOYMENT
##################################################
#region RESTART
systemctl restart nginx
systemctl restart php7.4-fpm
service mysql restart
#endregion RESTART
##################################################
