#!/bin/bash

printf '%s|%s' "$ALINA_PROFILE" "$ALINA_REMOTE_HOST"
printf '|%s' "$@"
printf '\n'
