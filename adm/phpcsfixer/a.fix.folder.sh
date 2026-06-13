#! /bin/bash

docker-compose -f dc.yml -f dc.dev.yml run --rm php-cs-fixer fix server/srv/alina/mvc


