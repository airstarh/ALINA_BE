#!/bin/bash

export ALINA_REMOTE_HOST="bbb"
export ALINA_REMOTE_USER="qqq"
export ALINA_REMOTE_URL="${ALINA_REMOTE_USER}@${ALINA_REMOTE_HOST}"
export ALINA_REMOTE_SSH="${BBB_REMOTE_SSH:-/home/qqq/.ssh/001}"

export A_R_BE="/mnt/d1001/_docker/az/ALINA_BE"
export A_R_VI="/mnt/d1001/_docker/az/ALINA_V"
export A_R_SRV="server/srv"
export A_R_VAR_WWW="server/var/www"
export A_R_GITOUT="_GITOUT"
export A_R_STORAGE="${A_R_BE}/${A_STORAGE}"

export ALINA_BASES=("zero")
export A_LIST_PROJECTS=("zero.home")
export ALINA_DEFAULT_PROJECT="zero.home"
