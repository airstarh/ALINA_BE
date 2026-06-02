Here’s a step-by-step guide to create self-signed certificates for multiple sites using a single configuration file, with **Nginx + PHP + MySQL + Docker**.

---

## 1. Prepare directory structure

On your **Docker host** (where Nginx will run), create a folder for certificates:

```bash
mkdir -p /docker/nginx/certs
cd /docker/nginx/certs
```

---

## 2. Create the OpenSSL configuration file

Create `san.conf`:

```bash
nano san.conf
```

Paste your template (extended with more useful defaults):

```ini
[req]
default_bits       = 2048
distinguished_name = req_distinguished_name
req_extensions     = req_ext
prompt             = no

[req_distinguished_name]
countryName            = RU
stateOrProvinceName    = Moscow
localityName           = Moscow
organizationName       = Home
organizationalUnitName = Local Network
commonName             = borg.home

[req_ext]
subjectAltName = @alt_names

[alt_names]
DNS.1 = borg.home
DNS.2 = a.borg.home
DNS.3 = x.borg.home
DNS.4 = zero.home
DNS.5 = localhost
IP.1   = 127.0.0.1
IP.2   = 192.168.1.86
IP.3   = 172.17.0.1

[ca]
default_ca = CA_default

[CA_default]
default_days     = 365
default_md       = sha256

[ extensions ]
basicConstraints       = CA:FALSE
authorityKeyIdentifier = keyid,issuer
keyUsage               = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
extendedKeyUsage       = serverAuth
subjectAltName         = @alt_names
```

---

## 3. Generate a self-signed certificate (valid for 365 days)

Run:

```bash
openssl req -x509 -newkey rsa:2048 -nodes \
  -keyout privkey.pem \
  -out fullchain.pem \
  -days 365 \
  -config san.conf \
  -extensions extensions
```

> `privkey.pem` – private key  
> `fullchain.pem` – certificate (with SAN extensions)

---

## 4. (Optional) Verify the certificate

```bash
openssl x509 -in fullchain.pem -text -noout | grep -A1 "Subject Alternative Name"
```

Expected output:

```
X509v3 Subject Alternative Name:
    DNS:borg.home, DNS:a.borg.home, DNS:x.borg.home, DNS:zero.home, DNS:localhost, IP Address:127.0.0.1, IP Address:192.168.1.86, IP Address:172.17.0.1
```

---

## 5. Set up Nginx inside Docker to use this certificate

Assume your Nginx Docker container mounts `/docker/nginx/certs` to `/etc/nginx/certs`.

Example `docker-compose.yml`:

```yaml
version: '3.8'
services:
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /docker/nginx/conf.d:/etc/nginx/conf.d
      - /docker/nginx/certs:/etc/nginx/certs
      - /docker/nginx/html:/var/www/html
    restart: unless-stopped
```

---

## 6. Create per-site Nginx configs (TLS example)

File `/docker/nginx/conf.d/borg.conf`:

```nginx
server {
    listen 443 ssl;
    server_name borg.home;

    ssl_certificate     /etc/nginx/certs/fullchain.pem;
    ssl_certificate_key /etc/nginx/certs/privkey.pem;

    root /var/www/html/borg;
    index index.php index.html;

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass php:9000;   # if PHP-FPM container
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Repeat for `a.borg.home`, `x.borg.home`, `zero.home` – or use a wildcard plus `server_name` directives in one block (but SAN must still match exactly).

---

## 7. Reload Nginx

```bash
docker exec <nginx_container_name> nginx -s reload
```

---

## 8. Trust the certificate on your local machines

### On **macOS**:
```bash
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain fullchain.pem
```

### On **Windows**:
- Double-click `fullchain.pem` → Install Certificate → Local Machine → Place in “Trusted Root Certification Authorities”

### On **Linux (Debian/Ubuntu)**:
```bash
sudo cp fullchain.pem /usr/local/share/ca-certificates/home-ca.crt
sudo update-ca-certificates
```

### On **Android / iOS**:
- Upload `fullchain.pem` to device, install as VPN & app CA.

---

## 9. Test in browser

Open `https://borg.home` (or any listed DNS name).  
You should see **"Connection secure"** (after trusting the CA), not a warning about mismatched names.

---

## Important notes

- Because it’s **self-signed**, browsers will still show a warning until you **manually trust** the certificate on each client.
- The `IP.2 = 192.168.1.86` must be your **Docker host’s real local IP**.
- For `zero.home` to work, add entries to your **local DNS** or `/etc/hosts`:

```
192.168.1.86 borg.home a.borg.home x.borg.home zero.home
```

- If you want a **wildcard certificate** (e.g., `*.borg.home`), change `DNS.1 = *.borg.home` – but SAN allows mixing exact and wildcard.

---

Let me know if you also need **automatic renewal** (though self-signed doesn’t expire as critically) or a script to regenerate for new sites added to the config.