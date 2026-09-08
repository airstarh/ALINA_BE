#!/bin/bash

export A_STORAGE="../STORAGE"
export SUB_SQL="backup/sql"
export SUB_DYN="backup/dyn"

export A_L_BE="$ALINA_ROOT"
export A_L_FE="/home/qqq/_A001/rep/ALINA_FE"
export A_L_VI="/home/qqq/_A001/rep/ALINA_V"

export A_L_SRV="server/srv"
export A_L_VAR_WWW="server/var/www"
export A_L_GITOUT="_GITOUT"
export A_L_STORAGE="${A_L_BE}/${A_STORAGE}"

export A_FRAMEWORK="alina"
export A_CONSUMERS="alina_consumers"

export A_LIST_CORE=(
    "$A_FRAMEWORK"
    "$A_CONSUMERS"
)

export MAX_EXECUTION_TIME_SECS="33"
export MAX_EXECUTION_TIME_MSECS="33000"
