#!/bin/bash
set -e

# 010.san.conf - Dynamically build the configuration file inline
cat << 'EOF' > 010.san.conf
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
commonName             = zero.home

[req_ext]
subjectAltName = @alt_names

[alt_names]
DNS.1 = borg.home
DNS.2 = default.org
DNS.3 = default.home
DNS.4 = zero.home
DNS.5 = localhost
IP.1  = 127.0.0.1
IP.2  = 192.168.1.86

[extensions]
basicConstraints       = CA:FALSE
keyUsage               = digitalSignature, keyEncipherment
extendedKeyUsage       = serverAuth
subjectAltName         = @alt_names
EOF

# 020.byrobot.privkey.pem - Generate the permanent server private key
openssl genrsa -out 020.byrobot.privkey.pem 2048

# 030.android_root.key - Generate the Certificate Authority private key
# 040.android_root.crt - Generate the Certificate Authority public certificate
openssl req -x509 -new -nodes \
  -keyout 030.android_root.key \
  -out 040.android_root.crt \
  -sha256 -days 1825 \
  -subj "/CN=ByRobot Mobile Root CA"

# 050.android_server.csr - Create the server certificate signing request (CSR)
openssl req -new \
  -key 020.byrobot.privkey.pem \
  -config 010.san.conf \
  -out 050.android_server.csr

# 060.android_server.crt - Sign the server certificate using the fresh Root CA files
openssl x509 -req \
  -in 050.android_server.csr \
  -CA 040.android_root.crt \
  -CAkey 030.android_root.key \
  -CAcreateserial \
  -out 060.android_server.crt \
  -days 365 -sha256 \
  -extfile 010.san.conf \
  -extensions extensions

# 070.byrobot.fullchain.pem - Overwrite the target fullchain bundle file for Nginx
cat 060.android_server.crt 040.android_root.crt > 070.byrobot.fullchain.pem

# 080.mobile_trust.crt - Export the Root CA to DER format for Android compatibility
openssl x509 -in 040.android_root.crt -outform DER -out 080.mobile_trust.crt

# ==============================================================================
# DEPLOYMENT MAP & FILE REFERENCE (All items inside current directory)
# ==============================================================================
#
# 1. NGINX CONFIGURATION (Adjust path strings to point here):
#    ssl_certificate /path/to/current/folder/070.byrobot.fullchain.pem;
#    ssl_certificate_key /path/to/current/folder/020.byrobot.privkey.pem;
#    -> Action: Run 'nginx -s reload' after executing this script.
#
# 2. DESKTOP BROWSERS (Chrome / Firefox):
#    -> Target File: 040.android_root.crt
#    -> Location: Settings > Privacy and Security > Security > Manage certificates > Authorities tab.
#    -> Action: Import and check "Trust this certificate for identifying websites".
#
# 3. ANDROID DEVICES:
#    -> Target File: 080.mobile_trust.crt
#    -> Location: Settings > Security > Encryption & credentials > Install a certificate > CA certificate.
#    -> Action: Transfer file to storage and select it to clear mobile TLS errors.
#
# ==============================================================================
