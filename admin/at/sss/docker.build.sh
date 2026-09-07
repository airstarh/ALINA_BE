#! /bin/bash

sudo bash ./adm/a/dev/perms.sh

docker compose \
    -f dc.all.yml \
    -f dc.all.php82.yml \
    -f dc.all.signal.yml \
    -f dc.prod.yml \
    build --no-cache

sudo bash ./adm/a/prod/perms.sh
