#! /bin/bash

docker compose -f dc.yml -f dc.prod.yml run --rm certbot renew --force-renewal --nginx
