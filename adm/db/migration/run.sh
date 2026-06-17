#!/bin/bash

source ./adm/s.includes.sh

DATABASES=("${ALINA_BASES[@]}")
PASSWORD=$MYSQL_ROOT_PASSWORD

SQL_DIR="./adm/db/migration"
ONCE_SQL_PATH="$SQL_DIR/sql.sql"

for DB in "${DATABASES[@]}"; do
    echo ""
    echo ">>> $DB"

    (cat "$ONCE_SQL_PATH") | docker exec -i alina_mysql mysql -u root -p"$PASSWORD" "$DB"

    echo "<<< $DB"
    echo ""
done
