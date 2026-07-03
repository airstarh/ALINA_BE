#! /bin/bash

docker compose -f dc.yml -f dc.prod.yml -f dc.prod..php82.yml build --no-cache php82