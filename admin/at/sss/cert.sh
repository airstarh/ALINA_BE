#!/bin/bash

docker compose \
  -f dc.all.yml \
  -f dc.prod.yml \
  run --rm \
  certbot \
  certonly \
  --webroot \
  --webroot-path=/var/www/certbot \
  --email vsevolod.azovsky@gmail.com \
  --agree-tos \
  --no-eff-email \
  -d ospl1942.ru \
  --cert-name ospl1942.ru \
  --force-renewal
