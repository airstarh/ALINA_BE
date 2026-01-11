#!/bin/sh
##################################################
# LOCAL
##################################################
#region /srv/
ssh "${REMOTE_ADDR}" 'sudo chown -R www-data:www-data /srv/'
ssh "${REMOTE_ADDR}" 'sudo find /srv -type d -exec chmod 755 {} +'
ssh "${REMOTE_ADDR}" 'sudo find /srv -type f -exec chmod 644 {} +'
#endregion /srv/
##################################################
#region /var/www/
ssh "${REMOTE_ADDR}" 'sudo chown -R www-data:www-data /var/www/'
ssh "${REMOTE_ADDR}" 'sudo find /var/www -type d -exec chmod 755 {} +'
ssh "${REMOTE_ADDR}" 'sudo find /var/www -type f -exec chmod 644 {} +'
#endregion /var/www/
##################################################
