#!/bin/bash

source "./admin/inc.sh"

cd "${ALINA_FE_LOCAL_DIR}"
####################################################################################################
# FRONT
# nvm install "16.19.1"
# nvm use "16.19.1"
# npm run build:all




cd "${DIR_THIS}" || { echo "Failed to cd to ${DIR_THIS}"; exit 1; }
####################################################################################################
# OWN
# source ./scripts/000.ownership.sh
# source ./scripts/010.upload-be.sh
source ./scripts/015.www-diff.sh
# source ./scripts/020.upload-web.sh
# source ./scripts/000.ownership.sh
# source ./scripts/030.restart.sh