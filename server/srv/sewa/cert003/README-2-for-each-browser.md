Understood — your paths and file names are correct, and you're pointing to the **new self-signed certificates**. The issue is that **Chromium on Kubuntu 26.04 still doesn't trust them**.

This is a common problem with self-signed certificates on modern Linux + Chrome/Chromium. Here's the complete fix:

---

## Why Chromium doesn't trust your cert

Chrome/Chromium on Linux uses **its own certificate store** (`nssdb`), not the system CA store. Even after `update-ca-certificates`, Chrome may ignore it.

---

## Solution: Add your self-signed cert to Chrome's NSS database

### Step 1: Convert your cert to DER format (required for NSS)

```bash
cd /srv/sewa/cert003/cert/
openssl x509 -in ./040.byrobot.fullchain.pem -outform DER -out 080.zero-home.der
```

### Step 2: Install `libnss3-tools` (if not already)

```bash
sudo apt install libnss3-tools

```

### Step 3: Add certificate to Chrome's trust store

```bash
# For Chrome/Chromium
certutil -d sql:$HOME/.pki/nssdb -A -t "C,," -n "Zero Home CA" -i ./040.byrobot.fullchain.pem
```

If that fails (database may not exist), initialize it first:

```bash
mkdir -p $HOME/.pki/nssdb
certutil -d sql:$HOME/.pki/nssdb -N --empty-password
certutil -d sql:$HOME/.pki/nssdb -A -t "C,," -n "Zero Home CA" -i /srv/sewa/cert003/cert/040.byrobot.fullchain.pem
```

### Step 4: Verify it was added

```bash
certutil -d sql:$HOME/.pki/nssdb -L
```

You should see `Zero Home CA` listed with trust attributes `C,,`

---

## Alternative method: Import via Chrome GUI

1. Open **Chromium**
2. Go to `chrome://settings/certificates`
3. Click **"Authorities"** tab
4. Click **"Import"**
5. Select your `040.byrobot.fullchain.pem` file
6. Check **"Trust this certificate for identifying websites"**
7. Click **OK**

---

## Firefox (separate store)

If you also use Firefox:

1. Open Firefox
2. `about:preferences#privacy`
3. Scroll to **Certificates** → **View Certificates**
4. **Authorities** tab → **Import**
5. Select your `.pem` file
6. Check **"Trust this CA to identify websites"**

---

## Critical check: Does your cert have the correct SAN?

Run this to verify:

```bash
openssl x509 -in /srv/sewa/cert003/cert/040.byrobot.fullchain.pem -text -noout | grep -A5 "Subject Alternative Name"
```

You **must** see:
```
DNS:zero.home
DNS:borg.home
DNS:a.borg.home
...
IP:192.168.1.86
```

If `zero.home` is missing, Chrome will reject it regardless of trust.

---

## If still not working: Check Chrome's error details

1. Open `chrome://badcast` or just go to `https://zero.home`
2. Click the padlock → **"Not secure"** → **"Certificate"**
3. Check:
   - Is the certificate your **new self-signed cert** (not an old one)?
   - Does the **Common Name** or **SAN** include `zero.home`?
4. Open Developer Tools (F12) → **Security** tab → View certificate chain

---

## Nuclear option: Launch Chrome ignoring certificate errors (testing only)

```bash
google-chrome --ignore-certificate-errors --unsafely-treat-insecure-origin-as-secure=https://zero.home https://zero.home
```

---

## Most likely remaining issue

Your certificate **Common Name (CN)** or **SAN** doesn't include `zero.home` exactly as requested.

Recreate the cert with this exact `san.conf` (ensuring `DNS.4 = zero.home` is present):

```ini
[req]
default_bits       = 2048
distinguished_name = req_distinguished_name
req_extensions     = req_ext
prompt             = no

[req_distinguished_name]
CN = zero.home

[req_ext]
subjectAltName = @alt_names

[alt_names]
DNS.1 = zero.home
DNS.2 = borg.home
DNS.3 = a.borg.home
DNS.4 = x.borg.home
IP.1  = 127.0.0.1
IP.2  = 192.168.1.86

[extensions]
basicConstraints       = CA:FALSE
keyUsage               = digitalSignature, keyEncipherment
extendedKeyUsage       = serverAuth
subjectAltName         = @alt_names
```

Then regenerate:

```bash
openssl req -x509 -newkey rsa:2048 -nodes \
  -keyout /srv/sewa/cert003/cert/030.byrobot.privkey.pem \
  -out /srv/sewa/cert003/cert/040.byrobot.fullchain.pem \
  -days 365 \
  -config san.conf \
  -extensions extensions
```

Restart Nginx and re-import the certificate to Chrome.

---

Let me know what `openssl x509 -text` shows for your cert and whether Chrome's certificate viewer shows the new cert.