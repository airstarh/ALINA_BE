#!/bin/sh
##################################################
# https://serverfault.com/questions/357108/what-permissions-should-my-website-files-folders-have-on-a-linux-webserver?newreg=39fc9a6627a248cea5726ab197bcab22
# @deprecated ssh -i C:\_A001\STATICA\_SSH\127-0-0-1 sewa@saysimsim.ru
# ssh sewa@saysimsim.ru
# sudo sh /srv/alina/programs/deploy/sh-chmods.sh
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
 chmod -R 770 /var/www/saysimsim.ru/uploads/
 chmod -R 770 /var/www/m45a/uploads/
 chmod -R 770 /var/www/vov/uploads/
 chmod -R 770 /var/www/stage/uploads/
#endregion UPLOADS
##################################################
#region HOME SEWA DEPLOYMENT
#@deprecated chown -R sewa /home/sewa/DEPLOYMENT/
#@deprecated chgrp -R www-data /home/sewa/DEPLOYMENT/
#@deprecated chmod -R 750 /home/sewa/DEPLOYMENT/
#@deprecated chmod -R u+x /home/sewa/DEPLOYMENT/
#endregion HOME SEWA DEPLOYMENT
##################################################
#region RESTART
systemctl restart nginx
systemctl restart php7.4-fpm
service mysql restart
#endregion RESTART
##################################################
