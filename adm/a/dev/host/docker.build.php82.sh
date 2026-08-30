#! /bin/bash

docker compose \
    -f dc.all.yml \
    -f dc.all.php82.yml \
    -f dc.all.signal.yml \
    -f dc.dev.yml \
    build --no-cache php82
