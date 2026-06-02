#! /bin/bash

#
# Ensure you have your certificate in the right format. Android supports .crt, .p12, .cer, and .pfx files
#
openssl x509 -in 040.byrobot.fullchain.pem -outform DER -out 090.zero-home.crt

#
# Transfer file to device and find a way to import
# 090.zero-home.crt
#