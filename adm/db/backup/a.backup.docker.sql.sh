#!/bin/bash

source ./adm/s.includes.sh

echo "Running..."

for db in "${ALINA_BASES[@]}"; do
    echo ""
    echo ">> $db"

    mkdir -p "./${A_R_GITOUT}/db"

    docker exec alina_mysql sh -c "
        MYSQL_PWD='${MYSQL_ROOT_PASSWORD}' mysql -u root -e \"
            SET PERSIST max_execution_time = 0;
            SELECT 'Global max_execution_time disabled for backup';
        \" 2>/dev/null
    "

    sleep 2

    docker exec alina_mysql sh -c "
        MYSQL_PWD='${MYSQL_ROOT_PASSWORD}' mysqldump \
            -u root \
            --databases '$db' \
            --add-drop-database \
            --add-drop-table \
            --complete-insert \
            --disable-keys \
            --single-transaction \
            --extended-insert=FALSE \
            --set-gtid-purged=OFF \
            --column-statistics=0 \
            --quote-names
    " | gzip > "./${A_R_GITOUT}/db/${db}.sql.gz"

    docker exec alina_mysql sh -c "
        MYSQL_PWD='${MYSQL_ROOT_PASSWORD}' mysql -u root -e \"
            SET PERSIST max_execution_time = $MAX_EXECUTION_TIME_MSECS;
            SELECT 'Global max_execution_time restored to $MAX_EXECUTION_TIME_MSECS mSecs';
        \" 2>/dev/null
    "

    if [ $? -eq 0 ]; then
        echo "<< $db"
    else
        echo "❌ Failed to dump $db"
        exit 1
    fi
    echo ""
done

echo "Finished"
