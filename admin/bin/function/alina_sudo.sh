#!/bin/bash

alina_sudo() {
    local script="${1:?admin script path is required}"
    shift

    command sudo bash "$ALINA_ADMIN/bin/sudo.run.sh" \
        "$ALINA_ADMIN" \
        "$ALINA_PROFILE_DIR" \
        "$ALINA_PROFILE" \
        "$script" \
        "$@"
}
