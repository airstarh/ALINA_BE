#! /bin/bash

openssl req -x509 -new -nodes -keyout android_root.key -sha256 -days 1825 -out android_root.crt -subj "/CN=ByRobot Mobile Root CA"
# 1. Create a request using your original private key and original SAN config
openssl req -new -key ../cert/030.byrobot.privkey.pem -config ../cert/010.san.conf -out android_server.csr
# 2. Sign it using the new root CA while preserving the [extensions] section layout
openssl x509 -req -in android_server.csr -CA android_root.crt -CAkey android_root.key -CAcreateserial -out android_server.crt -days 365 -sha256 -extfile 010.san.conf -extensions extensions
cat android_server.crt android_root.crt > ../cert/040.byrobot.fullchain.pem
openssl x509 -in android_root.crt -outform DER -out mobile_trust.crt
