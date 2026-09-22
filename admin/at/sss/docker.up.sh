#! /bin/bash

alina_sudo "${ALINA_ADMIN}/at/local/perms.sh"

docker compose \
    -f dc.all.yml \
    -f dc.all.php82.yml \
    -f dc.all.signal.yml \
    -f dc.prod.yml \
    up -d

alina_sudo "${ALINA_ADMIN}/at/sss/perms.sh"
alina_sudo "${ALINA_ADMIN}/at/sss/socet.restart.sh"
