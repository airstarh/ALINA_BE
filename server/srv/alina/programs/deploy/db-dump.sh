#!/bin/bash

PATH_SETUP_EXE="$(readlink -f "$0")"
DIR_SETUP_EXE="$(dirname "${PATH_SETUP_EXE}")"

source "${DIR_SETUP_EXE}/inc.sh"

#####

mkdir -p "${ALINA_LOCAL_DUMP_DIR}"

# Dump on remote and copy to local
for db in "${ALINA_DB_BASES[@]}"; do
    ssh "${REMOTE_ADDR}" \
        "mysqldump -u '${ALINA_DB_USER}' -p'${ALINA_DB_PASS}' \
        --databases '$db' \
        --add-drop-database \
        --replace \
        --single-transaction \
        --routines \
        --triggers \
        --events" > "${ALINA_LOCAL_DUMP_DIR}/db_${db}.sql"

    echo "Dumped: $db"
done