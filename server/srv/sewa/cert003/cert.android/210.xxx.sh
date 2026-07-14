#!/bin/bash
set -e

openssl req -x509 -new -nodes -keyout 300.android_root.key -sha256 -days 1825 -out 310.android_root.crt -subj "/CN=ByRobot Mobile Root CA"
openssl req -new -key ../cert/030.byrobot.privkey.pem -config ../cert/010.san.conf -out 320.android_server.csr
openssl x509 -req -in 320.android_server.csr -CA 310.android_root.crt -CAkey 300.android_root.key -CAcreateserial -out 330.android_server.crt -days 365 -sha256 -extfile ../cert/010.san.conf -extensions extensions
cat 330.android_server.crt 310.android_root.crt > ../cert/040.byrobot.fullchain.pem
openssl x509 -in 310.android_root.crt -outform DER -out 340.mobile_trust.crt
