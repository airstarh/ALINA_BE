#! /bin/bash

source ./adm/s.includes.sh

cd "${A_L_FE}" || { echo "Failed to cd to ${A_L_FE}"; exit 1; }
####################################################################################################
# FRONT
# nvm install "16.19.1"
# nvm use "16.19.1"
npm run build:all
####################################################################################################
cd "${A_L_BE}" || { echo "Failed to cd to ${A_L_BE}"; exit 1; }