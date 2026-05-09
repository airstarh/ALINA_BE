#!/bin/bash

DIR_THIS="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${DIR_THIS}" || exit
source "./inc.sh"

#####

# Database to restore - SET THIS VARIABLE
ALINA_DB_NAME_TO_RESTORE="database_name_here"

# Remote dumps directory (from your inc.sh)
REMOTE_DUMP_DIR="${ALINA_REMOTE_DUMP_DIR}"

# Local temporary directory for downloaded dump
TEMP_DIR="/tmp/db_restore_$$"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if database name is set
if [ -z "${ALINA_DB_NAME_TO_RESTORE}" ] || [ "${ALINA_DB_NAME_TO_RESTORE}" = "database_name_here" ]; then
    echo -e "${RED}ERROR: Please set ALINA_DB_NAME_TO_RESTORE variable in the script${NC}"
    exit 1
fi

# Check if remote dump exists
echo "Checking for remote dump: ${REMOTE_DUMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql.gz"
if ! ssh "${REMOTE_ADDR}" "test -f ${REMOTE_DUMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql.gz"; then
    echo -e "${RED}ERROR: Dump file not found: ${REMOTE_DUMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql.gz${NC}"

    # List available dumps
    echo -e "${YELLOW}Available dumps on remote:${NC}"
    ssh "${REMOTE_ADDR}" "ls -lh ${REMOTE_DUMP_DIR}/*.sql.gz 2>/dev/null | awk '{print \$9, \"(\" \$5 \")\"}'"
    exit 1
fi

# Confirm restoration
echo -e "${YELLOW}WARNING: This will OVERWRITE the database '${ALINA_DB_NAME_TO_RESTORE}' on remote host${NC}"
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "${CONFIRM}" != "yes" ]; then
    echo "Restoration cancelled."
    exit 0
fi

# Create temp directory
mkdir -p "${TEMP_DIR}"
echo "Created temporary directory: ${TEMP_DIR}"

# Track time
START_TIME=$(date +%s)

echo ""
echo "Starting restoration of database: ${ALINA_DB_NAME_TO_RESTORE}"

# Step 1: Download the gzipped dump from remote
echo "Downloading compressed dump from remote..."
scp "${REMOTE_ADDR}:${REMOTE_DUMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql.gz" "${TEMP_DIR}/"

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Failed to download dump from remote${NC}"
    rm -rf "${TEMP_DIR}"
    exit 1
fi

# Step 2: Get file size
FILE_SIZE=$(ls -lh "${TEMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql.gz" | awk '{print $5}')
echo "Downloaded: ${ALINA_DB_NAME_TO_RESTORE}.sql.gz (${FILE_SIZE})"

# Step 3: Decompress
echo "Decompressing dump..."
gunzip "${TEMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql.gz"

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Failed to decompress dump${NC}"
    rm -rf "${TEMP_DIR}"
    exit 1
fi

# Step 4: Restore to remote database
echo "Restoring to remote database '${ALINA_DB_NAME_TO_RESTORE}'..."
ssh "${REMOTE_ADDR}" \
    "mysql -u '${ALINA_DB_USER}' -p'${ALINA_DB_PASS}' \
    -e 'DROP DATABASE IF EXISTS ${ALINA_DB_NAME_TO_RESTORE}; \
    CREATE DATABASE ${ALINA_DB_NAME_TO_RESTORE};'"

# Restore the database
cat "${TEMP_DIR}/${ALINA_DB_NAME_TO_RESTORE}.sql" | ssh "${REMOTE_ADDR}" \
    "mysql -u '${ALINA_DB_USER}' -p'${ALINA_DB_PASS}' \
    ${ALINA_DB_NAME_TO_RESTORE}"

if [ $? -eq 0 ]; then
    # Calculate time
    END_TIME=$(date +%s)
    TOTAL_TIME=$((END_TIME - START_TIME))
    TOTAL_MINUTES=$((TOTAL_TIME / 60))
    TOTAL_SECONDS=$((TOTAL_TIME % 60))

    echo ""
    echo -e "${GREEN}=========================================${NC}"
    echo -e "${GREEN}Database restored successfully!${NC}"
    echo "Database: ${ALINA_DB_NAME_TO_RESTORE}"
    echo "Total time: ${TOTAL_TIME} seconds (${TOTAL_MINUTES} minutes ${TOTAL_SECONDS} seconds)"
    echo -e "${GREEN}=========================================${NC}"
else
    echo -e "${RED}ERROR: Failed to restore database${NC}"
fi

# Cleanup
echo "Cleaning up temporary files..."
rm -rf "${TEMP_DIR}"

echo ""
echo "Restoration complete!"