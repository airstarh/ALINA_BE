#! /bin/bash

docker compose \
    -f dc.all.yml \
    -f dc.all.php82.yml \
    -f dc.prod.yml \
    build --no-cache php82
