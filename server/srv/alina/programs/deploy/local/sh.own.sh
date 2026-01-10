#!/bin/sh
##################################################
# LOCAL
##################################################
#region /srv/
ssh sewa@saysimsim.ru  'sudo chown -R www-data:www-data /srv/'
ssh sewa@saysimsim.ru  'sudo find /srv -type d -exec chmod 755 {} +'
ssh sewa@saysimsim.ru  'sudo find /srv -type f -exec chmod 644 {} +'
#endregion /srv/
##################################################
#region /var/www/
ssh sewa@saysimsim.ru 'sudo chown -R www-data:www-data /var/www/'
ssh sewa@saysimsim.ru 'sudo find /var/www -type d -exec chmod 755 {} +'
ssh sewa@saysimsim.ru 'sudo find /var/www -type f -exec chmod 644 {} +'
#endregion /var/www/
##################################################
