#! /bin/bash
# delete
# certutil -d sql:$HOME/.pki/nssdb -D -n "zero.home"

# install
certutil -d sql:$HOME/.pki/nssdb -A -t "P,," -n "zero.home" -i ./040.byrobot.fullchain.pem
# region diag
certutil -d sql:$HOME/.pki/nssdb -L | grep -E "zero|home"
openssl x509 -in 040.byrobot.fullchain.pem -text -noout | head -20

# Fingerprint of your file
openssl x509 -in 040.byrobot.fullchain.pem -noout -fingerprint
# Fingerprint stored in Chrome
certutil -d sql:$HOME/.pki/nssdb -L -n "zero.home" | grep -A1 "Fingerprint"

# Most useful - see exact trust flags:
certutil -d sql:$HOME/.pki/nssdb -L -n "zero.home" -a

curl -v --cacert 040.byrobot.fullchain.pem https://zero.home:50443 2>&1 | head -20
# endregion diag