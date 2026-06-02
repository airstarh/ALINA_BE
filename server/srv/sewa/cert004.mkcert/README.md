## mkcert in 4 steps:

### 1. Install
```bash
sudo apt install libnss3-tools
wget https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-linux-amd64
chmod +x mkcert-v1.4.4-linux-amd64
sudo mv mkcert-v1.4.4-linux-amd64 /usr/local/bin/mkcert
```

### 2. Create local CA (one time only)
```bash
mkcert -install
```
*This adds a trusted CA to your system/browsers*

### 3. Generate certificates
```bash
cd /srv/sewa/cert003/cert/
mkcert zero.home borg.home a.borg.home x.borg.home localhost 192.168.1.86
```
*Creates `zero.home+5.pem` and `zero.home+5-key.pem`*

### 4. Use in Nginx
```nginx
ssl_certificate /srv/sewa/cert003/cert/zero.home+5.pem;
ssl_certificate_key /srv/sewa/cert003/cert/zero.home+5-key.pem;
```

**That's it. Browsers trust it automatically. No `certutil` commands needed.**