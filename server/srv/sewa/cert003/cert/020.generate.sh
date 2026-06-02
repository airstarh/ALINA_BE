#! /bin/bash

openssl req -x509 -newkey rsa:2048 -nodes \
  -keyout 030.byrobot.privkey.pem \
  -out 040.byrobot.fullchain.pem \
  -days 365 \
  -config 010.san.conf \
  -extensions extensions