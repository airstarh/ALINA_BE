#! /bin/bash

docker compose \
    -f dc.yml \
    -f dc.php82.yml \
    -f dc.prod.signal.yml \
    -f dc.prod.yml \
    down
