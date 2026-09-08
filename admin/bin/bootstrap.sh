#!/bin/bash

set -euo pipefail
IFS=$'\n\t'

alina_bootstrap() {
    local profile="${1:-}"
    local profile_file

    ALINA_ADMIN="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
    ALINA_ROOT="$(cd "$ALINA_ADMIN/.." && pwd)"
    profile_file="$ALINA_ADMIN/bin/config/host/$profile.sh"

    if [[ -z "$profile" || ! -f "$profile_file" ]]; then
        echo "Unknown admin profile: ${profile:-<empty>}" >&2
        return 2
    fi

    export ALINA_ADMIN
    export ALINA_ROOT
    export ALINA_PROFILE="$profile"

    source "$ALINA_ADMIN/../_GITOUT/env/.env"
    source "$ALINA_ADMIN/bin/config/common.sh"
    # The validated profile name intentionally selects one configuration file.
    # shellcheck disable=SC1090
    source "$profile_file"

    source "$ALINA_ADMIN/bin/function/asd.sh"
    source "$ALINA_ADMIN/bin/function/alina_rsync_from_remote.sh"
    source "$ALINA_ADMIN/bin/function/alina_rsync_local.sh"
    source "$ALINA_ADMIN/bin/function/alina_rsync_to_local_wwwdiff.sh"
    source "$ALINA_ADMIN/bin/function/alina_rsync_to_remote.sh"
}
