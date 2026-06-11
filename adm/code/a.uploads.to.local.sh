#! /bin/bash

source ./adm/s.includes.sh

for LOC_PROJECT in "${A_LIST_PROJECTS[@]}"; do

    LOC_TARGET="${A_L_BE}/${A_L_VAR_WWW}/${LOC_PROJECT}/uploads"
    LOC_SOURCE="${A_R_BE}/${A_R_VAR_WWW}/${LOC_PROJECT}/uploads"

    alina_rsync_from_remote "${LOC_TARGET}" "${LOC_SOURCE}"

done