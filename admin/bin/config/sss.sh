#! /bin/bash

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region COMMON
# export SUFFIX="$(date "+%Y-%m-%d---%H.%M:%S")"
export A_STORAGE="../STORAGE"
export SUB_SQL="backup/sql"
export SUB_DYN="backup/dyn"
# endregion COMMON
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region SERVER
export ALINA_REMOTE_HOST="ospl1942.ru"
export ALINA_REMOTE_USER="sewa"
export ALINA_REMOTE_URL="${ALINA_REMOTE_USER}@${ALINA_REMOTE_HOST}"
export ALINA_REMOTE_SSH="/home/qqq/.ssh/001"
# endregion SERVER
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region Databes

export ALINA_BASES=(
    "zero"
    "vov"
)

export ALINA_BASES
# endregion Databes
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region CODE
export A_L_BE="/home/qqq/_A001/rep/ALINA_BE"
export A_L_FE="/home/qqq/_A001/rep/ALINA_FE"
export A_L_VI="/home/qqq/_A001/rep/ALINA_V"

export A_L_SRV="server/srv"
export A_L_VAR_WWW="server/var/www"
export A_L_GITOUT="_GITOUT"
export A_L_STORAGE="${A_L_BE}/${A_STORAGE}"

export A_R_BE="/home/sewa/_A001/rep/ALINA_BE"
export A_R_VI="/home/sewa/_A001/rep/ALINA_V"
export A_R_SRV="server/srv"
export A_R_VAR_WWW="server/var/www"
export A_R_GITOUT="_GITOUT"
export A_R_STORAGE="${A_R_BE}/${A_STORAGE}"

export A_FRAMEWORK="alina"
export A_CONSUMERS="alina_consumers"
export A_LIST_CORE=(
    "${A_FRAMEWORK}"
    "${A_CONSUMERS}"
)

export A_LIST_PROJECTS=(
    "zero.home"
    "vov"
)
export ALINA_DEFAULT_PROJECT="zero.home"
# endregion CODE
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region INFRASTRUCTURE

export MAX_EXECUTION_TIME_SECS="33"
export MAX_EXECUTION_TIME_MSECS="33000"

# endregion INFRASTRUCTURE
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region XXX
echo ''
echo "CONSTANTS RETRIEVED AT ::: $(asd)" "$(dsa)"
echo $ALINA_ADMIN
echo ''
# endregion XXX
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
