##################################################
ssh -i C:\_A001\STATICA\_SSH\127-0-0-1 sewa@saysimsim.ru
##################################################
sudo chown www-data:www-data /srv -R
sudo chmod -R 755 /srv
##################################################
sudo usermod -a -G www-data sewa
##################################################
#region RIGHTS
# CHOWN
sudo chown www-data:www-data /srv/alina/_backend -R
sudo chown www-data:www-data /srv/php/_backend -R
sudo chown www-data:www-data /var/www/saysimsim.ru -R
sudo chown www-data:www-data /var/www/vov -R
sudo chown www-data:www-data /var/www/m45a -R
#####
# CHMOD
sudo chmod -R 755 /srv/alina/_backend
sudo chmod -R 755 /srv/php/_backend
sudo chmod -R 755 /var/www/saysimsim.ru
sudo chmod -R 755 /var/www/vov
sudo chmod -R 755 /var/www/m45a
#endregion RIGHTS
##################################################
#region RESTART
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
sudo service mysql restart
sudo service ssh restart
#endregion RESTART
##################################################
chown -R sewa /var/www/
chgrp -R www-data /var/www/
chmod -R 750 /var/www/
chmod g+s /var/www/
##################################################
chown -R sewa /srv/
chgrp -R www-data /srv/
chmod -R 750 /srv/
chmod g+s /srv/
##################################################
