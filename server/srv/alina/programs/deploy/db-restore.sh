#!/bin/bash

PATH_SETUP_EXE="$(readlink -f "$0")"
DIR_SETUP_EXE="$(dirname "${PATH_SETUP_EXE}")"

source "${DIR_SETUP_EXE}/inc.sh"

#####

docker exec alina_mysql mysql -u "${ALINA_DB_USER}" -p"${ALINA_DB_PASS}" <<EOSQL
SET FOREIGN_KEY_CHECKS=0;
SET UNIQUE_CHECKS=0;
SET AUTOCOMMIT=0;
SOURCE ${ALINA_DB_DUMPS_DIR}/db_m45a.sql;
COMMIT;
SET FOREIGN_KEY_CHECKS=1;
SET UNIQUE_CHECKS=1;
EOSQL

