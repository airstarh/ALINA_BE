#! /bin/bash

sudo bash ./adm/a/dev/perms.sh

docker compose \
    -f dc.all.yml \
    -f dc.all.php82.yml \
    -f dc.all.signal.yml \
    -f dc.prod.yml \
    up -d

sudo bash ./adm/a/prod/perms.sh
sudo bash ./adm/a/prod/socet.restart.sh
