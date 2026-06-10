#!/bin/bash


for LOC_PROJECT in "${A_LIST_CORE[@]}"; do

    LOC_SOURCE="${A_L_BE}/${A_L_SRV}/${LOC_PROJECT}/"
    LOC_TARGET="${A_R_SRV}/${LOC_PROJECT}/"

    alina_rsync_ssh_server "${LOC_SOURCE}" "${LOC_TARGET}"

done