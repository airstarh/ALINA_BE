#!/bin/bash

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

DIFF_BASE="zero.home"
DIFF_DEFAULT="${BE}/server/srv/alina_consumers/${DIFF_BASE}/.WwwDiff/"

PROJECTS_WWW_DIFF=("zero.home" "stage" "saysimsim.ru" "m45a" "vov")

for PROJECT in "${PROJECTS_WWW_DIFF[@]}"; do
    DIFF_PROJECT="${BE}/server/srv/alina_consumers/${PROJECT}/.WwwDiff/"
    TO_FINAL_PLACE="${BE}/server/var/www/${PROJECT}/"
    rsyncLocal "${DIFF_DEFAULT}" "${DIFF_PROJECT}" "${TO_FINAL_PLACE}"
done
