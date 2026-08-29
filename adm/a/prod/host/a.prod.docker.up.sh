#! /bin/bash

sudo bash ./a.dev.perms.sh

docker compose \
    -f dc.yml \
    -f dc.php82.yml \
    -f dc.prod.yml \
    up -d

sudo bash ./a.prod.perms.sh
