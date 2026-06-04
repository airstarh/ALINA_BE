#!/bin/bash

DIR_THIS="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${DIR_THIS}" || exit
source "../inc.sh"

#####

# Database to restore - SET THIS VARIABLE
ALINA_DB_NAME_TO_RESTORE="stage"

# Check if database name is set
if [ -z "${ALINA_DB_NAME_TO_RESTORE}" ] || [ "${ALINA_DB_NAME_TO_RESTORE}" = "database_name_here" ]; then
    echo "ERROR: Database name not set"
    exit 1
fi

# Check if remote dump exists
if ! ssh "${REMOTE_ADDR}" "test -f ${ALINA_REMOTE_DUMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql.gz"; then
    echo "ERROR: Dump file not found: ${ALINA_REMOTE_DUMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql.gz"
    exit 1
fi

# Track time
START_TIME=$(date +%s)

# Restore directly on remote server
if ssh "${REMOTE_ADDR}" \
    "gunzip -c ${ALINA_REMOTE_DUMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql.gz | \
    mysql -u '${ALINA_DB_USER}' -p'${ALINA_DB_PASS}'"; then

    END_TIME=$(date +%s)
    TOTAL_TIME=$((END_TIME - START_TIME))
    TOTAL_MINUTES=$((TOTAL_TIME / 60))
    TOTAL_SECONDS=$((TOTAL_TIME % 60))

    echo "SUCCESS: ${ALINA_DB_NAME_TO_RESTORE} restored in ${TOTAL_TIME} seconds (${TOTAL_MINUTES}m ${TOTAL_SECONDS}s)"
else
    echo "ERROR: Failed to restore database ${ALINA_DB_NAME_TO_RESTORE}"
    exit 1
fi