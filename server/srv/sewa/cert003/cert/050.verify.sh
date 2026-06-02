#! /bin/bash

openssl x509 -in 040.byrobot.fullchain.pem -text -noout | grep -A1 "Subject Alternative Name"
