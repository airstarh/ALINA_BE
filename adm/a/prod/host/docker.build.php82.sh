#! /bin/bash

docker compose \
    -f dc.yml \
    -f dc.php82.yml \
    -f dc.prod.yml \
    build --no-cache php82
