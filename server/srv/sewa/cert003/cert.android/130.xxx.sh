#! /bin/bash

# 1. Create a CSR (Certificate Signing Request) using your key
openssl req -new -key ../cert/030.byrobot.privkey.pem -out 140.server.csr -subj "/CN=zero.home"

# 2. Sign it as a valid leaf certificate
openssl x509 -req -in 140.server.csr -CA 110.AndroidCA.crt -CAkey ../cert/030.byrobot.privkey.pem -CAcreateserial -out 150.new_server.crt -days 365 -sha256 -extfile 120.server.ext
