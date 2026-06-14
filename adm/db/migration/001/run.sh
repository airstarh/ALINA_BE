#!/bin/bash

source ./adm/s.includes.sh

DATABASES=$ALINA_BASES
PASSWORD=$MYSQL_ROOT_PASSWORD

for DB in "${DATABASES[@]}"; do

    echo ""
    echo ">>> $DB"

    docker exec -i alina_mysql mysql -u root -p"$PASSWORD" "$DB" < ./adm/db/migration/001/migrate.watch_ip.sql

    echo "<<< $DB"
    echo ""
done
