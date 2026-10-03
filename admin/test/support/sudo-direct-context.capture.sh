#!/bin/bash

printf '%s|%s' "$(type -t asd)" "$(asd)"
printf '|%s' "$@"
printf '\n'
