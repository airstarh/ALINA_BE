##################################################
ssh -i C:\_A001\STATICA\_SSH\127-0-0-1 sewa@saysimsim.ru
##################################################
sudo sh /home/sewa/DEPLOYMENT/sh-chmods.sh
##################################################
#region RESTART
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
sudo service mysql restart
sudo service ssh restart
#endregion RESTART
##################################################
