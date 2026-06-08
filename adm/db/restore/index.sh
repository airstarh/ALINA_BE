#! /bin/bash

source ./adm/s.includes.sh

LOC_DB="stage"
zcat ./_GITOUT/db/${LOC_DB}.sql.gz | docker exec -i alina_mysql mysql -u root -p"${MYSQL_ROOT_PASSWORD}"

