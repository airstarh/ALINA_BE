#!/bin/bash

alina_sudo() {
    sudo \
        SSH_AUTH_SOCK="$SSH_AUTH_SOCK" \
        HOME="$HOME" \
        ALINA_ADMIN="$ALINA_ADMIN" \
        ALINA_PROFILE_DIR="$ALINA_PROFILE_DIR" \
        ALINA_PROFILE="$ALINA_PROFILE" \
        bash -c '
            source "$ALINA_ADMIN/bin/bootstrap.sh"
            alina_bootstrap "$ALINA_PROFILE"
            "$@"
        ' bash "$@"
}
