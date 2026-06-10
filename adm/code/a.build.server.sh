#!/bin/bash

source ./adm/s.includes.sh

cd "${A_L_FE}"
####################################################################################################
# FRONT
# nvm install "16.19.1"
# nvm use "16.19.1"
# npm run build:all




cd "${DIR_THIS}" || { echo "Failed to cd to ${DIR_THIS}"; exit 1; }
####################################################################################################
# OWN
source ./adm/bin/script/code/010.wwwdiff.build.sh
# source ./adm/bin/script/code/020.www.to.remote.sh
# source ./adm/bin/script/code/030.alina.to.remote.sh