#!/bin/bash

source ./adm/s.includes.sh

DATABASES=("${ALINA_BASES[@]}")
PASSWORD=$MYSQL_ROOT_PASSWORD
SQL_FILE_NAME="001.sgl.sql"

for DB in "${DATABASES[@]}"; do

    echo ""
    echo ">>> $DB"
    echo ""

    docker exec -i alina_mysql mysql -u root -p"$PASSWORD" "$DB" < ./adm/db/migration/$SQL_FILE_NAME

    echo ""
    echo "<<< $DB"
    echo ""
done
