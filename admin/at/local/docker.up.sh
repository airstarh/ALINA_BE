#! /bin/bash

alina_sudo "$(asd)"/perms.sh

docker compose \
    -f dc.all.yml \
    -f dc.all.php82.yml \
    -f dc.all.signal.yml \
    -f dc.dev.yml \
    up -d

alina_sudo "$(asd)"/perms.sh
