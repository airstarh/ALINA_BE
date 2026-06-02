#! /bin/bash

# On Linux trust
sudo cp 040.byrobot.fullchain.pem /usr/local/share/ca-certificates/home-ca.crt
sudo update-ca-certificates