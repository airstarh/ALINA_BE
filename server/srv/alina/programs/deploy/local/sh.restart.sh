##################################################
#region RESTART

ssh sewa@saysimsim.ru 'sudo systemctl restart nginx'
ssh sewa@saysimsim.ru 'sudo systemctl restart php7.4-fpm'
ssh sewa@saysimsim.ru 'sudo service mysql restart'

#endregion RESTART
##################################################
