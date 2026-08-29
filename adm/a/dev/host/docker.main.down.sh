#! /bin/bash

docker compose \
    -f dc.yml \
    -f dc.php82.yml \
    -f dc.signal.yml \
    -f dc.dev.yml \
    down
