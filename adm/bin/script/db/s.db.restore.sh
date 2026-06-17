#! /bin/bash

zcat ./_GITOUT/db/${LOC_DB}.sql.gz | docker exec -i alina_mysql mysql \
  --binary-mode \
  -u root \
  -p"${MYSQL_ROOT_PASSWORD}" \
  --init-command="SET autocommit=0, unique_checks=0, foreign_key_checks=0;"
