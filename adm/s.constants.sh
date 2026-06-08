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

export ALINA_BASES=(
    "alina" # sss
    "stage" # stage/zero.home
    "vov"
    "m45a"
)

export ALINA_FOLDERS=(
    "saysimsim.ru"
    "zero.home"     # uses DB stage
    "stage"         # uses DB stage
    "m45a"
    "vov"
    # "chat"
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
