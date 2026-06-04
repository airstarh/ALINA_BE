#!/bin/bash
# shellcheck disable=SC2034

####################################################################################################

export REMOTE_USER="sewa"
export REMOTE_HOST="saysimsim.ru"
export REMOTE_ADDR="${REMOTE_USER}@${REMOTE_HOST}"

####################################################################################################

# Code
export ALINA_BE_LOCAL_DIR="/home/qqq/_A001/rep/ALINA_BE"
export ALINA_FE_LOCAL_DIR="/home/qqq/_A001/rep/ALINA_FE"

export ALINA_DOCKER_VOL_BE="server/srv"
export ALINA_DOCKER_VOL_FE="server/var/www"

export ALINA_BE_REMOTE_DIR="/srv"
export ALINA_FE_REMOTE_DIR="/var/www"

export ALINA_FRAMEWORK="alina"
export ALINA_FRAMEWORK_CONSUMERS="alina_consumers"
export ALINA_BE_FREAMEWORK_FOLDERS=(
    "${ALINA_FRAMEWORK}"
    "${ALINA_FRAMEWORK_CONSUMERS}"
)

export ALINA_PROJECTS=(
    "zero.home"
    "stage"
    "saysimsim.ru"
    "m45a"
    "vov"
)
export ALINA_DEFAULT_PROJECT="zero.home"

####################################################################################################
# Databsase
export ALINA_DB_USER="root"
export ALINA_DB_PASS="1378862"
export ALINA_DB_BASES=("alina" "m45a" "stage" "vov")

export ALINA_DOCKER_DB_USER="root"
export ALINA_DOCKER_DB_PASS="borg_root_pass"

export ALINA_LOCAL_DUMP_DIR="/home/qqq/dumps"
export ALINA_DOCKER_DUMPS_DIR="/tmp/dumps"
export ALINA_REMOTE_DUMP_DIR="/home/sewa/dumps"

####################################################################################################