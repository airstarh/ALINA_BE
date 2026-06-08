#! /bin/bash

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region SERVER
export ALINA_REMOTE_HOST="saysimsim.ru"
export ALINA_REMOTE_USER="sewa"
export ALINA_REMOTE_URL="${ALINA_REMOTE_HOST}@${ALINA_REMOTE_USER}"
# endregion SERVER
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region XXX

ALINA_ROOT=$(pwd)
export ALINA_ROOT

export ALINA_FOLDERS=(
    "stage"
    "alina" # sss
    "m45a"
    "vov"
)

export ALINA_BASES=(
    "zero.home"
    "stage"
    "saysimsim.ru"
    "m45a"
    "vov"
)
# endregion XXX

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# region XXX
echo ''
echo 'CONSTANTS RETRIEVED'
echo $ALINA_ROOT
echo ''
# endregion XXX
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
