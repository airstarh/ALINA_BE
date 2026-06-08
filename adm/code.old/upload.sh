#!/bin/bash

source ./adm/s.includes.sh

cd "${ALINA_FE_LOCAL_DIR}"
####################################################################################################
# FRONT
# nvm install "16.19.1"
# nvm use "16.19.1"
# npm run build:all




cd "${DIR_THIS}" || { echo "Failed to cd to ${DIR_THIS}"; exit 1; }
####################################################################################################
# OWN
# source ./adm/code.old/scripts/000.ownership.sh
# source ./adm/code.old/scripts/010.upload-be.sh
source ./adm/code.old/scripts/015.www-diff.sh
# source ./adm/code.old/scripts/020.upload-web.sh
# source ./adm/code.old/scripts/000.ownership.sh
# source ./adm/code.old/scripts/030.restart.sh