#! /bin/bash

openssl pkcs12 -export \
  -in ../cert/040.byrobot.fullchain.pem \
  -inkey ../cert/030.byrobot.privkey.pem \
  -out 200.zero-home.p12 \
  -name "zero.home"

