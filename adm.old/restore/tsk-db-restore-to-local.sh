#!/bin/bash

source "./admin/inc.sh"

DUMP_FILE_NAME="stage.sql"

echo ""
echo "Starting import..."

START_TIME=$(date +%s)

docker exec alina_mysql mysql \
  -u "${ALINA_DOCKER_DB_USER}" \
  -p"${ALINA_DOCKER_DB_PASS}" \
  --batch \
  --quick \
  --max_allowed_packet=2G \
  --net_buffer_length=1M \
  -e "SET FOREIGN_KEY_CHECKS=0; SET UNIQUE_CHECKS=0; SET AUTOCOMMIT=0;" \
  -e "SOURCE ${ALINA_DOCKER_DUMPS_DIR}/${DUMP_FILE_NAME};" \
  -e "COMMIT; SET FOREIGN_KEY_CHECKS=1; SET UNIQUE_CHECKS=1;"

END_TIME=$(date +%s)
TOTAL_TIME=$((END_TIME - START_TIME))

echo ""
echo "Total import time: ${TOTAL_TIME} seconds ($((TOTAL_TIME / 60)) minutes and $((TOTAL_TIME % 60)) seconds)"
echo ""