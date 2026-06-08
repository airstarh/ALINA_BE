#!/bin/bash

DIR_THIS="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${DIR_THIS}" || exit
source "../inc.sh"

#####

# Remote dumps directory

# Create remote directory
ssh "${REMOTE_ADDR}" "mkdir -p ${ALINA_REMOTE_DUMP_DIR}"

# Track total time
TOTAL_START_TIME=$(date +%s)

# Dump, zip, and delete original on remote
for db in "${ALINA_DB_BASES[@]}"; do
    DB_START_TIME=$(date +%s)

    echo ""
    echo "Starting dump of database: $db"

    # Execute dump, zip, and remove SQL file all on remote
ssh "${REMOTE_ADDR}" \
  "mysqldump -u '${ALINA_DB_USER}' -p'${ALINA_DB_PASS}' \
    --databases '$db' \
    --add-drop-database \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --complete-insert \
    | gzip > ${ALINA_REMOTE_DUMP_DIR}/${db}.sql.gz && \
    echo 'SUCCESS'"

    DB_END_TIME=$(date +%s)
    DB_TOTAL_TIME=$((DB_END_TIME - DB_START_TIME))

    # Get zipped file size from remote
    FILE_SIZE=$(ssh "${REMOTE_ADDR}" "ls -lh ${ALINA_REMOTE_DUMP_DIR}/${db}.sql.gz" | awk '{print $5}')

    echo "Dumped and compressed: $db (${FILE_SIZE}) - Time: ${DB_TOTAL_TIME} seconds"
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
echo "Dumps stored remotely in: ${ALINA_REMOTE_DUMP_DIR}"
echo "========================================="