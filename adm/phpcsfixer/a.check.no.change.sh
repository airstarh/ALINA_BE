#! /bin/bash

# docker-compose -f dc.yml -f dc.dev.yml run --rm php-cs-fixer fix --dry-run --diff
docker-compose -f dc.yml -f dc.dev.yml run --rm php-cs-fixer fix server/srv/alina/mvc --dry-run --diff
