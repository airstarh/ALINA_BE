#!/bin/bash

PATH_SETUP_EXE="$(readlink -f "$0")"
DIR_SETUP_EXE="$(dirname "${PATH_SETUP_EXE}")"

source "${DIR_SETUP_EXE}/inc.sh"

#####

docker exec -i alina_mysql mysql -u root -pborg_root_pass < "${ALINA_LOCAL_DUMP_DIR}/db_m45a.sql"
