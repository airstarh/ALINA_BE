#!/bin/bash

export ALINA_REMOTE_HOST="ospl1942.ru"
export ALINA_REMOTE_USER="sewa"
export ALINA_REMOTE_URL="${ALINA_REMOTE_USER}@${ALINA_REMOTE_HOST}"
export ALINA_REMOTE_SSH="/home/qqq/.ssh/001"

export A_R_BE="/home/${ALINA_REMOTE_USER}/_A001/rep/ALINA_BE"
export A_R_VI="/home/${ALINA_REMOTE_USER}/_A001/rep/ALINA_V"
export A_R_SRV="server/srv"
export A_R_VAR_WWW="server/var/www"
export A_R_GITOUT="_GITOUT"
export A_R_STORAGE="${A_R_BE}/${A_STORAGE}"

export ALINA_BASES=("zero" "vov")
export A_LIST_PROJECTS=("zero.home" "vov")
export ALINA_DEFAULT_PROJECT="zero.home"
