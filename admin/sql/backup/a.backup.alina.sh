#!/bin/bash
# sewa
source ./adm/s.includes.sh
db="alina"
echo "Running..."

echo ""
echo ">> $db"

mkdir -p "./${A_R_GITOUT}/db"

# Increase timeout and buffer settings for large DB
docker exec alina_mysql sh -c "
    MYSQL_PWD='${MYSQL_ROOT_PASSWORD}' mysql -u root -e \"
        SET GLOBAL max_execution_time = 0;
        SET GLOBAL net_read_timeout = 3600;
        SET GLOBAL net_write_timeout = 3600;
        SET GLOBAL wait_timeout = 28800;
        SET GLOBAL interactive_timeout = 28800;
        SET SESSION max_allowed_packet = 1073741824;
        SELECT 'Global timeouts increased for backup';
    \" 2>/dev/null
"

sleep 2

# Dump in chunks with row-based streaming to avoid memory exhaustion
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
        --quote-names \
        --max_allowed_packet=1G \
        --net_buffer_length=16384 \
        --quick \
        --skip-opt \
        --skip-lock-tables \
        --skip-add-locks \
        --order-by-primary \
        --hex-blob
" | gzip -c | pv -pterb -s $(docker exec alina_mysql sh -c "MYSQL_PWD='${MYSQL_ROOT_PASSWORD}' mysql -u root -N -e 'SELECT SUM(data_length+index_length) FROM information_schema.tables WHERE table_schema=\"$db\"'") > "./${A_R_GITOUT}/db/${db}.sql.gz" 2>/dev/null

# Check if dump succeeded
if [ ${PIPESTATUS[0]} -eq 0 ]; then
    echo "<< $db"
else
    echo "❌ Failed to dump $db"
    # Restore settings before exit
    docker exec alina_mysql sh -c "
        MYSQL_PWD='${MYSQL_ROOT_PASSWORD}' mysql -u root -e \"
            SET GLOBAL max_execution_time = $MAX_EXECUTION_TIME_MSECS;
            SET GLOBAL net_read_timeout = 30;
            SET GLOBAL net_write_timeout = 30;
            SET GLOBAL wait_timeout = 28800;
            SET GLOBAL interactive_timeout = 28800;
            SELECT 'Settings restored';
        \" 2>/dev/null
    "
    exit 1
fi

# Restore original settings
docker exec alina_mysql sh -c "
    MYSQL_PWD='${MYSQL_ROOT_PASSWORD}' mysql -u root -e \"
        SET GLOBAL max_execution_time = $MAX_EXECUTION_TIME_MSECS;
        SET GLOBAL net_read_timeout = 30;
        SET GLOBAL net_write_timeout = 30;
        SET GLOBAL wait_timeout = 28800;
        SET GLOBAL interactive_timeout = 28800;
        SELECT 'Global settings restored to normal';
    \" 2>/dev/null
"

echo ""
echo "Finished"
