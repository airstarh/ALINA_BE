#! /bin/bash

docker compose \
    -f dc.all.yml \
    -f dc.all.php82.yml \
    -f dc.cam.yml \
    -f dc.all.signal.yml \
    -f dc.dev.yml \
    down
