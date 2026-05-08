#!/bin/bash

PATH_SETUP_EXE="$(readlink -f "$0")"
DIR_SETUP_EXE="$(dirname "${PATH_SETUP_EXE}")"
source "${DIR_SETUP_EXE}/inc.sh"

echo "Starting import at $(date)"

docker exec alina_mysql mysql \
  -u "${ALINA_LOCAL_DB_USER}" \
  -p"${ALINA_LOCAL_DB_PASS}" \
  --batch \
  --quick \
  --max_allowed_packet=3G \
  --net_buffer_length=1M \
  -e "SET FOREIGN_KEY_CHECKS=0; SET UNIQUE_CHECKS=0; SET AUTOCOMMIT=0;" \
  -e "SOURCE ${ALINA_LOCAL_DB_DUMPS}/db_m45a.sql;" \
  -e "COMMIT; SET FOREIGN_KEY_CHECKS=1; SET UNIQUE_CHECKS=1;"

echo "Import finished at $(date)"
