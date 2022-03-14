##################################################
ssh -i C:\_A001\STATICA\_SSH\127-0-0-1 sewa@saysimsim.ru
##################################################
#region RIGHTS
# CHOWN
sudo chown www-data:www-data /srv/alina/_backend -R
sudo chown www-data:www-data /var/www/saysimsim.ru -R
sudo chown www-data:www-data /var/www/vov -R
sudo chown www-data:www-data /var/www/m45a -R
#####
# CHMOD
sudo chmod -R 755 /srv/alina/_backend
sudo chmod -R 755 /var/www/saysimsim.ru
sudo chmod -R 755 /var/www/vov
sudo chmod -R 755 /var/www/m45a
#endregion RIGHTS
##################################################
#region RESTART
sudo systemctl restart nginx

#endregion RESTART
##################################################


