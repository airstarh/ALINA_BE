#!/bin/bash
# shellcheck disable=SC2034

####################################################################################################

export REMOTE_USER="sewa"
export REMOTE_HOST="saysimsim.ru"
export REMOTE_ADDR="${REMOTE_USER}@${REMOTE_HOST}"

####################################################################################################
# Databsase
export ALINA_DB_USER="root"
export ALINA_DB_PASS="1378862"
export ALINA_DB_BASES=("alina" "m45a" "stage" "vov")

export ALINA_DOCKER_DB_USER="root"
export ALINA_DOCKER_DB_PASS="borg_root_pass"

export ALINA_LOCAL_DUMP_DIR="./_GITOUT/db/dev"
export ALINA_DOCKER_DUMPS_DIR="/tmp/dumps"
export ALINA_REMOTE_DUMP_DIR="/home/sewa/dumps"

####################################################################################################