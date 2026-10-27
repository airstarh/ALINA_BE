#!/bin/bash

SQL_DIR="$ALINA_ADMIN/sql/history"
START_FROM="${1:-}"

mapfile -t SQL_FILES < <(find "$SQL_DIR" -maxdepth 1 -type f -name '*.sql' -printf '%f\n' | sort -V)

if [ "${#SQL_FILES[@]}" -eq 0 ]; then
    echo "No SQL history scripts found in: $SQL_DIR" >&2
    exit 1
fi

if [ -n "$START_FROM" ]; then
    MIGRATION_FOUND=0
    for SQL_FILE in "${SQL_FILES[@]}"; do
        if [ "$SQL_FILE" = "$START_FROM" ]; then
            MIGRATION_FOUND=1
            break
        fi
    done

    if [ "$MIGRATION_FOUND" -ne 1 ]; then
        echo "Migration not found: $START_FROM" >&2
        exit 1
    fi
fi

for DB in "${ALINA_BASES[@]}"; do
    echo ""
    echo ">>> $DB"

    for SQL_FILE in "${SQL_FILES[@]}"; do
        if [ -n "$START_FROM" ] && [ "$(printf '%s\n%s\n' "$START_FROM" "$SQL_FILE" | sort -V | head -n 1)" != "$START_FROM" ]; then
            continue
        fi

        SQL_PATH="$SQL_DIR/$SQL_FILE"
        echo "Running $SQL_FILE"

        if ! docker exec -i alina_mysql mysql \
            -u root \
            -p"$MYSQL_ROOT_PASSWORD" \
            "$DB" \
            < "$SQL_PATH"; then
            echo "Failed to execute $SQL_FILE on $DB" >&2
            exit 1
        fi
    done

    echo "<<< $DB"
    echo ""
done
