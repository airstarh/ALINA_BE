#! /bin/bash

#! /bin/bash

docker compose -f dc.yml -f dc.dev.yml run --rm \
  -v "$PWD/server/srv/alina/composer.json:/apps/composer.json:ro" \
  php-cs-fixer fix \
    server/srv/alina_consumers \
    server/srv/alina \
    server/var/www \
  --config=.php-cs-fixer.dist.php \
  --cache-file=/tmp/.php-cs-fixer.cache \
  --allow-risky=yes
