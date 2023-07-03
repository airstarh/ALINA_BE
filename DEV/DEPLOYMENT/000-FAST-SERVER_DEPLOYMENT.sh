##################################################
ssh -i C:\_A001\STATICA\_SSH\127-0-0-1 sewa@saysimsim.ru
##################################################
#region /srv/
sudo chown -R sewa /srv/
sudo chgrp -R www-data /srv/
sudo chmod -R 750 /srv/
sudo chmod g+s /srv/
#endregion /srv/
##################################################
#region /var/www/
sudo chown -R sewa /var/www/
sudo chgrp -R www-data /var/www/
sudo chmod -R 750 /var/www/
sudo chmod g+s /var/www/
#endregion /var/www/
##################################################
#region UPLOADS
sudo chmod -R 770 /var/www/saysimsim.ru/uploads
sudo chmod -R 770 /var/www/m45a/uploads
sudo chmod -R 770 /var/www/vov/uploads
#endregion UPLOADS
##################################################





##################################################
#region RESTART
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
sudo service mysql restart
sudo service ssh restart
#endregion RESTART
##################################################





##################################################
# OLD:
##################################################
sudo chown www-data:www-data /srv -R
sudo chmod -R 755 /srv
##################################################
sudo usermod -a -G www-data sewa
##################################################
#region RIGHTS
# CHOWN
sudo chown www-data:www-data /srv/php/_backend -R
sudo chown www-data:www-data /var/www -R
#####
# CHMOD
sudo chmod -R 755 /srv/php/_backend
sudo chmod -R 755 /var/www
#endregion RIGHTS
##################################################

