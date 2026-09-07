#! /bin/bash

# var
ALINA_ADMIN="${BASH_SOURCE[0]%/*}"

# FUNCTION
. "${ALINA_ADMIN}"/bin/function/asd.sh
#
. $(asd)/bin/function/alina_rsync_from_remote.sh
. $(asd)/bin/function/alina_rsync_local.sh
. $(asd)/bin/function/alina_rsync_to_local_wwwdiff.sh
. $(asd)/bin/function/alina_rsync_to_remote.sh

# CONFIG
. $(asd)/bin/config/sss.sh
