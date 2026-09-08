#!/bin/bash

SQL_DIR="$ALINA_ADMIN/sql/migrate"
ONCE_SQL_PATH="$SQL_DIR/sql.sql"

for DB in "${ALINA_BASES[@]}"; do
    echo ""
    echo ">>> $DB"

    docker exec -i alina_mysql mysql \
        -u root \
        -p"$MYSQL_ROOT_PASSWORD" \
        "$DB" \
        < "$ONCE_SQL_PATH"

    echo "<<< $DB"
    echo ""
done
