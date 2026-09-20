#! /bin/bash

alina_sudo "${ALINA_ADMIN}"/at/local/perms.sh

docker compose \
    -f dc.all.yml \
    -f dc.all.php82.yml \
    -f dc.all.signal.yml \
    -f dc.prod.yml \
    build --no-cache

alina_sudo "$(asd)"/perms.sh
