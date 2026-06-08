#! /bin/bash

source ./adm/s.includes.sh

docker exec -i alina_mysql mysql -u root -p"${MYSQL_ROOT_PASSWORD}" < ./_GITOUT/db/stage.sql
