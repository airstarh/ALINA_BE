#! /bin/bash

sudo bash "${ALINA_ADMIN}"/at/local/perms.sh

docker compose \
    -f dc.all.yml \
    -f dc.all.php82.yml \
    -f dc.all.signal.yml \
    -f dc.prod.yml \
    up -d

sudo bash "$(asd)"/perms.sh
sudo bash "$(asd)"/socet.restart.sh
