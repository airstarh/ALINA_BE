#! /bin/bash

set -euo pipefail
IFS=$'\n\t'

# var
ALINA_ADMIN="${BASH_SOURCE[0]%/*}"
. "${ALINA_ADMIN}"/bin/function/asd.sh
ALINA_ADMIN=$(asd)

# ENV
. "$ALINA_ADMIN"/../_GITOUT/env/.env

# FUNCTION
. "$ALINA_ADMIN"/bin/function/alina_rsync_from_remote.sh
. "$ALINA_ADMIN"/bin/function/alina_rsync_local.sh
. "$ALINA_ADMIN"/bin/function/alina_rsync_to_local_wwwdiff.sh
. "$ALINA_ADMIN"/bin/function/alina_rsync_to_remote.sh

# CONFIG
. "$ALINA_ADMIN"/bin/config/sss.sh
