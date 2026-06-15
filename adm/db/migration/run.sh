#!/bin/bash

source ./adm/s.includes.sh

DATABASES=("${ALINA_BASES[@]}")
PASSWORD=$MYSQL_ROOT_PASSWORD

SQL_DIR="./adm/db/migration/mig.indexes.duplicate"
COMMON_SQL_PATH="$SQL_DIR/000.common.code.sql"
SQL_FILES=(
    "test.sql"
    "001.watch_ip.sql"
    "002.watch_browser.sql"
    "003.watch_banned_ip.sql"
    "004.watch_banned_visit.sql"
    "005.watch_banned_browser.sql"
    "006.watch_login.sql"
    "007.watch_url_path.sql"
    "008.file.sql"
    "009.login.sql"
    "010.pm_work_story.sql"
    "011.rbac_role.sql"
    "012.rbac_user_role.sql"
    "013.router_alias.sql"
    "014.tag_to_entity.sql"
    "015.tag.sql"
    "016.timezone.sql"
    "017.user_role.sql"
    "018.user.sql"
)

for DB in "${DATABASES[@]}"; do
    echo ""
    echo ">>> $DB"

    for SQL_FILE in "${SQL_FILES[@]}"; do
        echo "  > $SQL_FILE";

        FILE_PATH="$SQL_DIR/$SQL_FILE"
        (cat "$FILE_PATH"; cat "$COMMON_SQL_PATH") | docker exec -i alina_mysql mysql -u root -p"$PASSWORD" "$DB"
        # (cat "$SQL_DIR/test.sql") | docker exec -i alina_mysql mysql -u root -p"$PASSWORD" "$DB"

        echo "  < $SQL_FILE";
    done

    echo "<<< $DB"
    echo ""
done
