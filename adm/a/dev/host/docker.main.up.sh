#! /bin/bash

docker compose \
    -f dc.yml \
    -f dc.php82.yml \
    -f dc.dev.signal.yml \
    -f dc.dev.yml \
    up -d
