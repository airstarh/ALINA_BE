#!/bin/bash

DIR_THIS="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${DIR_THIS}" || exit
source "./inc.sh"

#####

mkdir -p "${ALINA_LOCAL_DUMP_DIR}"

# Track total time
TOTAL_START_TIME=$(date +%s)

# Dump on remote and copy to local
for db in "${ALINA_DB_BASES[@]}"; do
    DB_START_TIME=$(date +%s)

    echo ""
    echo "Starting dump of database: $db"

    ssh "${REMOTE_ADDR}" \
        "mysqldump -u '${ALINA_DB_USER}' -p'${ALINA_DB_PASS}' \
        --databases '$db' \
        --add-drop-database \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --complete-insert" > "${ALINA_LOCAL_DUMP_DIR}/${db}.sql"

    DB_END_TIME=$(date +%s)
    DB_TOTAL_TIME=$((DB_END_TIME - DB_START_TIME))

    # Get file size
    FILE_SIZE=$(ls -lh "${ALINA_LOCAL_DUMP_DIR}/${db}.sql" | awk '{print $5}')

    echo "Dumped: $db (${FILE_SIZE}) - Time: ${DB_TOTAL_TIME} seconds"
    echo ""
done

# Calculate and display total time
TOTAL_END_TIME=$(date +%s)
TOTAL_TIME=$((TOTAL_END_TIME - TOTAL_START_TIME))
TOTAL_MINUTES=$((TOTAL_TIME / 60))
TOTAL_SECONDS=$((TOTAL_TIME % 60))

echo ""
echo "========================================="
echo "All dumps completed!"
echo "Total time: ${TOTAL_TIME} seconds (${TOTAL_MINUTES} minutes ${TOTAL_SECONDS} seconds)"
echo "========================================="