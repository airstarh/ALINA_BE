#! /bin/bash

zcat ${A_L_STORAGE}/backup/sql/${LOC_DB}.sql.gz | docker exec -i alina_mysql mysql \
  --binary-mode \
  -u root \
  -p"${MYSQL_ROOT_PASSWORD}" \
  --init-command="SET autocommit=0, unique_checks=0, foreign_key_checks=0;"
