#!/bin/sh
##################################################
# https://serverfault.com/questions/357108/what-permissions-should-my-website-files-folders-have-on-a-linux-webserver?newreg=39fc9a6627a248cea5726ab197bcab22
# @deprecated ssh -i C:\_A001\STATICA\_SSH\127-0-0-1 sewa@saysimsim.ru
# ssh sewa@saysimsim.ru
# sudo sh /srv/alina/programs/deploy/sh-chmods.sh
##################################################
#region /srv/
chown -R www-data:www-data /srv
chmod g+s /srv
find /srv -type d -exec chmod 775 {} +
find /srv -type f -exec chmod 660 {} +
#endregion /srv/
##################################################
#region /var/www/
chown -R www-data:www-data /var/www/
chmod g+s /var/www
find /var/www -type d -exec chmod 775 {} +
find /var/www -type f -exec chmod 660 {} +
#endregion /var/www/
##################################################
#region RESTART

# systemctl restart nginx
# systemctl restart php7.4-fpm
# service mysql restart

#endregion RESTART
##################################################
