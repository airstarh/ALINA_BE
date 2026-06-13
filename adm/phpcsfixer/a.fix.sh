#! /bin/bash

docker compose -f dc.yml -f dc.dev.yml run --rm php-cs-fixer fix server/srv/alina --cache-file=/tmp/.php-cs-fixer.cache --allow-risky=yes
docker compose -f dc.yml -f dc.dev.yml run --rm php-cs-fixer fix server/srv/alina_consumers --cache-file=/tmp/.php-cs-fixer.cache --allow-risky=yes
docker compose -f dc.yml -f dc.dev.yml run --rm php-cs-fixer fix server/var/www --cache-file=/tmp/.php-cs-fixer.cache --allow-risky=yes
