#! /bin/bash

docker compose -f dc.yml -f dc.dev.yml -f dc.dev.php82.yml -f dc.dev.video.yml up -d
