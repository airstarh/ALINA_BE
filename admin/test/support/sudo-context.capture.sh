#!/bin/bash

remote_state=missing
arrays_state=missing

[[ -n "${ALINA_REMOTE_HOST:-}" ]] && remote_state=remote-set
if (( ${#ALINA_BASES[@]} > 0 && ${#A_LIST_PROJECTS[@]} > 0 )); then
    arrays_state=arrays-set
fi

printf '%s|%s|%s|%s|%s' \
    "$ALINA_PROFILE" \
    "$remote_state" \
    "$arrays_state" \
    "$(type -t asd)" \
    "$HOME"
printf '|%s' "$@"
printf '\n'
