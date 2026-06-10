#! /bin/bash

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region SERVER
export ALINA_REMOTE_HOST="saysimsim.ru"
export ALINA_REMOTE_USER="sewa"
export ALINA_REMOTE_URL="${ALINA_REMOTE_HOST}@${ALINA_REMOTE_USER}"
# endregion SERVER
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region Databes

export ALINA_BASES=(
    "alina" # sss
    "stage" # stage/zero.home
    "vov"
    "m45a"
)
# endregion Databes
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region CODE
export A_BE="."
export A_FE="./../ALINA_FE"

export A_L_SERVER_SRV="server/srv"
export A_L_VAR_WWW="server/var/www"

export A_R_SRV="/srv"
export A_R_VAR_WWW="/var/www"

export A_FRAMEWORK="alina"
export A_CONSUMERS="alina_consumers"
export A_LIST_CORE=(
    "${A_FRAMEWORK}"
    "${A_CONSUMERS}"
)

export A_LIST_PROJECTS=(
    "zero.home"
    "stage"
    "saysimsim.ru"
    "m45a"
    "vov"
)
export ALINA_DEFAULT_PROJECT="zero.home"
# endregion CODE
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region XXX
echo ''
echo 'CONSTANTS RETRIEVED'
echo $ALINA_ROOT
echo ''
# endregion XXX
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
