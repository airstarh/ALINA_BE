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
export ALINA_BE_LOCAL_DIR="/home/qqq/_A001/rep/ALINA_BE"
export ALINA_FE_LOCAL_DIR="/home/qqq/_A001/rep/ALINA_FE"

export ALINA_DOCKER_VOL_BE="server/srv"
export ALINA_DOCKER_VOL_FE="server/var/www"

export ALINA_BE_REMOTE_DIR="/srv"
export ALINA_FE_REMOTE_DIR="/var/www"

export ALINA_FRAMEWORK="alina"
export ALINA_FRAMEWORK_CONSUMERS="alina_consumers"
export ALINA_BE_FREAMEWORK_FOLDERS=(
    "${ALINA_FRAMEWORK}"
    "${ALINA_FRAMEWORK_CONSUMERS}"
)

export ALINA_PROJECTS=(
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
