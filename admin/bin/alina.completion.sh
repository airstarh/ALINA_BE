#!/bin/bash

_alina_complete() {
    local current="${COMP_WORDS[COMP_CWORD]}"
    local admin_dir
    local candidates

    admin_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

    case "$COMP_CWORD" in
        1)
            candidates="$(find "$admin_dir/bin/config/host" \
                -maxdepth 1 -type f -name '*.sh' -printf '%f\n' \
                | sed 's/\.sh$//' \
                | sort)"
            ;;
        2)
            candidates="$(find "$admin_dir" \
                -type f -name '*.sh' -printf '%P\n' \
                | sort)"
            ;;
        *)
            return
            ;;
    esac

    mapfile -t COMPREPLY < <(compgen -W "$candidates" -- "$current")
}

complete -F _alina_complete alina ./alina "$ALINA_COMMAND_DIR/alina"
