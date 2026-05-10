#!/bin/bash

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

for PROJECT in "${ALINA_PROJECTS[@]}"; do

    DIFF="${BE}/server/srv/${ALINA_FRAMEWORK_CONSUMERS}/${PROJECT}/.WwwDiff/"
    TO_FINAL_PLACE="${BE}/server/var/www/${PROJECT}/"

    rsyncLocal "${ALINA_DEFAULT_PROJECT_DIFF}" "${DIFF}" "${TO_FINAL_PLACE}"

done
