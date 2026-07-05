#! /bin/bash

sudo bash ./a.dev.perms.sh
docker compose -f dc.yml -f dc.prod.yml -f dc.prod.php82.yml up -d
sudo bash ./a.prod.perms.sh