#! /bin/bash

source ./adm/s.includes.sh

zcat ./_GITOUT/db/stage.sql.gz | docker exec -i alina_mysql mysql -u root -p"${MYSQL_ROOT_PASSWORD}"

