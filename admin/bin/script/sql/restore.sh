#! /bin/bash

LOC_L_STORE="${A_STORAGE}/${SUB_SQL}"
BACKUP_FILE="${LOC_L_STORE}/${LOC_DB}.sql.gz"

zcat "${BACKUP_FILE}" \
    | docker exec -i alina_mysql mysql \
        --binary-mode \
        -u root \
        -p"${MYSQL_ROOT_PASSWORD}" \
        --init-command="SET autocommit=0, unique_checks=0, foreign_key_checks=0;"
