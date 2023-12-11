##################################################
# SYMBOLIC LINK
ln -s /etc/nginx/sites-available/www.example.org.conf /etc/nginx/sites-enabled/
# USER MANAGEMENT
sudo usermod -a -G groupName userName
sudo usermod -a -G www-data sewa
sudo chmod -R 775 /srv/alina
sudo chown -R www-data:www-data /srv/alina
sudo chown -R sewa:sudo /srv/alina
sudo id -u www-data
sudo id -g www-data
# WinSCP
sudo su -c /usr/lib/sftp-server
# RESTART
service php7.4-fpm restart
sudo service nginx restart

##################################################
# FIREWALL
sudo ufw app list
sudo ufw allow 'Nginx HTTP'
sudo ufw status
##################################################
# NGINX
# https://www.digitalocean.com/community/tutorials/how-to-install-nginx-on-ubuntu-20-04-ru
sudo apt update
sudo apt install nginx
sudo ln -s /FROM /TO
sudo ln -s /EXISTING /NEW
sudo ln -s -v /etc/nginx/sites-available/host.home.conf /etc/nginx/sites-enabled/host.home.conf
sudo ln -s -v /etc/nginx/sites-available/borg-001.conf /etc/nginx/sites-enabled/borg-001.conf
sudo systemctl enable nginx
sudo systemctl status nginx
sudo nginx -t
sudo service nginx reload
sudo service nginx restart
sudo service nginx stop

##################################################
# PHP
sudo apt-get install php7.4-fpm php7.4-cli php7.4-mysql php7.4-curl php7.4-json php7.4-dom -y
sudo systemctl start php7.4-fpm
sudo systemctl restart php7.4-fpm
sudo systemctl enable php7.4-fpm
#####
# DOM extension
sudo apt-get install php-dom
#####
# Image Magick
### variant.1
# https://gist.github.com/danielstgt/dc1068e577bbd8b6e9a6050a6db1f9c3
sudo apt install imagemagick
convert -version
#wget https://gist.githubusercontent.com/danielstgt/dc1068e577bbd8b6e9a6050a6db1f9c3/raw/4687280a25513ce825f3ffcd31661b67f5896850/imagick3.4.4-PHP7.4-forge.sh
#sudo bash imagick3.4.4-PHP7.4-forge.sh
### variant.2
# https://ourcodeworld.com/articles/read/645/how-to-install-imagick-for-php-7-in-ubuntu-16-04
sudo apt-get install php7.4-imagick
php -m | grep imagick
#####
service php7.4-fpm restart

##################################################
# SSL
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /srv/sewa-srv/ssl/priv.key -out /srv/sewa-srv/ssl/pub.cert
### CertBot
# https://codeburst.io/how-to-install-and-use-snap-on-ubuntu-18-04-9fcb6e3b34f9
# https://certbot.eff.org/lets-encrypt/ubuntufocal-nginx
sudo su
sudo apt install snapd
sudo snap install core; sudo snap refresh core
sudo apt-get remove certbot
sudo snap install --classic certbot
####
# variant 1
sudo certbot --ngin
# variant 2
sudo certbot certonly --nginx
###
sudo certbot renew --dry-run
####

##################################################
# SQL
# https://computingforgeeks.com/how-to-install-mysql-8-on-ubuntu/
sudo wget https://dev.mysql.com/get/mysql-apt-config_0.8.16-1_all.deb
sudo dpkg -i mysql-apt-config_0.8.16-1_all.deb
sudo apt-get update
sudo apt-cache policy mysql-server
sudo apt-get install mysql-server -y
sudo mysql_secure_installation
sudo service mysql status
sudo service mysql stop
sudo service mysql start
sudo service mysql restart
sudo mysql -u root -p
SELECT host FROM mysql.user WHERE User = 'root';
CREATE USER 'root'@'%' IDENTIFIED BY '13021985qwaszx'; GRANT ALL PRIVILEGES ON *.* TO 'root'@'%';

##################################################
# SSH
# https://linuxize.com/post/how-to-enable-ssh-on-ubuntu-20-04/
sudo apt update
sudo apt install openssh-server
sudo systemctl status ssh
sudo ufw allow ssh
ip a
# sudo systemctl disable --now ssh
sudo systemctl enable --now ssh
sudo service ssh restart
# SSH KEY
# https://www.digitalocean.com/community/tutorials/how-to-set-up-ssh-keys-on-ubuntu-20-04-ru
# # https://phoenixnap.com/kb/setup-passwordless-ssh
# https://docs.microsoft.com/ru-ru/powershell/scripting/learn/remoting/ssh-remoting-in-powershell-core?ranMID=46131&ranEAID=a1LgFw09t88&ranSiteID=a1LgFw09t88-nb7cRity6mm32KaVeIDbBA&epi=a1LgFw09t88-nb7cRity6mm32KaVeIDbBA&irgwc=1&OCID=AID2000142_aff_7806_1243925&tduid=(ir__m0eola0a9kkftlhekk0sohz3xm2xuqtbyxl9o3j900)(7806)(1243925)(a1LgFw09t88-nb7cRity6mm32KaVeIDbBA)()&irclickid=_m0eola0a9kkftlhekk0sohz3xm2xuqtbyxl9o3j900&view=powershell-7.1
# https://docs.microsoft.com/ru-ru/windows-server/administration/openssh/openssh_keymanagement
# https://docs.microsoft.com/ru-ru/azure/virtual-machines/linux/ssh-from-windows

##### PowerShell
ssh-keygen -t rsa -b 4096 -C "root@127.0.0.1"
##### Copy from Win Host to Linux Host
##### SH
ssh-copy-id -f -i /root/.ssh/127-0-0-1.pub root@127.0.0.1
##### Powershell
ssh -i C:\_A001\STATICA\_SSH\127-0-0-1 root@host.home
ssh -i C:\_A001\STATICA\_SSH\127-0-0-1 sewa@borg-001.private
##### sudo vi /etc/ssh/sshd_config
ChallengeResponseAuthentication no
PasswordAuthentication no
UsePAM no
PermitRootLogin prohibit-password
AllowUsers sewa root
#####
sudo systemctl reload ssh

##################################################
# FTP
##################################################
# BIND
##################################################
# MAIL