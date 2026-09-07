#! /bin/bash

. ./adm/s.includes.sh

for LOC_PROJECT in "${A_LIST_PROJECTS[@]}"; do

    LOC_SOURCE="${A_L_BE}/${A_L_VAR_WWW}/${LOC_PROJECT}/uploads"
    LOC_TARGET="${A_R_BE}/${A_R_VAR_WWW}/${LOC_PROJECT}/uploads"

    alina_rsync_to_remote "${LOC_SOURCE}" "${LOC_TARGET}"

done
