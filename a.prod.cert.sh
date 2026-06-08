#! /bin/bash

docker compose \
  -f dc.yml \
  -f dc.prod.yml \
  run --rm \
  certbot \
  certonly \
  --webroot \
  --webroot-path=/var/www/certbot \
  --email vsevolod.azovsky@gmail.com \
  --agree-tos \
  --no-eff-email \
  -d saysimsim.ru \
  -d mironova45a.ru \
  -d ospl1942.ru \
  -d stage.saysimsim.ru \

# docker compose -f dc.yml exec nginx nginx -s reload
