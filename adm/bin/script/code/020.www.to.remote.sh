#!/usr/bin/bash

for LOC_PROJECT in "${A_LIST_PROJECTS[@]}"; do

    LOC_SOURCE="${A_L_BE}/${A_L_VAR_WWW}/${LOC_PROJECT}/"
    LOC_TARGET="${A_R_VAR_WWW}/${LOC_PROJECT}/"

    alina_rsync_ssh_server "${LOC_SOURCE}" "${LOC_TARGET}"

done
