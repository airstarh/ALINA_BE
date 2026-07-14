#!/bin/bash
set -e

# 1. Generate the Local Root CA Private Key and Public Certificate
# 300.android_root.key - The private key for the Certificate Authority
# 310.android_root.crt - The public Certificate Authority file
openssl req -x509 -new -nodes \
  -keyout 300.android_root.key \
  -out 310.android_root.crt \
  -sha256 -days 1825 \
  -subj "/CN=ByRobot Mobile Root CA"

# 2. Create the Server Certificate Signing Request (CSR)
# 320.android_server.csr - The signing request built from your existing key and SAN layout
openssl req -new \
  -key ../cert/030.byrobot.privkey.pem \
  -config ../cert/010.san.conf \
  -out 320.android_server.csr

# 3. Sign the Server Certificate using the fresh Root CA files
# 330.android_server.crt - The public server leaf certificate
openssl x509 -req \
  -in 320.android_server.csr \
  -CA 310.android_root.crt \
  -CAkey 300.android_root.key \
  -CAcreateserial \
  -out 330.android_server.crt \
  -days 365 -sha256 \
  -extfile ../cert/010.san.conf \
  -extensions extensions

# 4. Overwrite the existing fullchain bundle file for Nginx
# ../cert/040.byrobot.fullchain.pem - Combines server leaf and root CA into the target path
cat 330.android_server.crt 310.android_root.crt > ../cert/040.byrobot.fullchain.pem

# 5. Export the Root CA to DER format for Android compatibility
# 340.mobile_trust.crt - The binary file specifically structured for mobile installations
openssl x509 -in 310.android_root.crt -outform DER -out 340.mobile_trust.crt

# ==============================================================================
# DEPLOYMENT MAP & DIRECTORY USAGE REFERENCE
# ==============================================================================
#
# 1. NGINX CONFIGURATION (Keep exactly as is):
#    ssl_certificate /srv/sewa/cert003/cert/040.byrobot.fullchain.pem;
#    ssl_certificate_key /srv/sewa/cert003/cert/030.byrobot.privkey.pem;
#    -> Action: Run 'nginx -s reload' after executing this script.
#
# 2. DESKTOP BROWSERS (Chrome / Firefox):
#    -> Target File: cert.android/310.android_root.crt
#    -> Location: Settings > Privacy and Security > Security > Manage certificates > Authorities tab.
#    -> Action: Import and check "Trust this certificate for identifying websites".
#
# 3. ANDROID DEVICES:
#    -> Target File: cert.android/340.mobile_trust.crt
#    -> Location: Settings > Security > Encryption & credentials > Install a certificate > CA certificate.
#    -> Action: Transfer file to storage and select it to clear mobile TLS errors.
#
# ==============================================================================
