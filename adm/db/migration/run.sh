#!/bin/bash

source ./adm/s.includes.sh

DATABASES=("${ALINA_BASES[@]}")
PASSWORD=$MYSQL_ROOT_PASSWORD

SQL_DIR="./adm/db/migration/mig.indexes.duplicate"
SQL_FILES=(
    "test.sql"
    # "001.watch_ip.sql"
)

for DB in "${DATABASES[@]}"; do
    echo ""
    echo ">>> $DB"

    for SQL_FILE in "${SQL_FILES[@]}"; do
        echo "  > $SQL_FILE";

        FILE_PATH="$SQL_DIR/$SQL_FILE"
        docker exec -i alina_mysql mysql -u root -p"$PASSWORD" "$DB" < "$FILE_PATH"

        echo "  < $SQL_FILE";
    done

    echo "<<< $DB"
    echo ""
done
