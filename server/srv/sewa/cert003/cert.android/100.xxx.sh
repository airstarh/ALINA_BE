#! /bin/bash

openssl req -x509 -new -nodes -key ../cert/030.byrobot.privkey.pem -sha256 -days 1825 -out 110.AndroidCA.crt -subj "/CN=ByRobot Local CA"
