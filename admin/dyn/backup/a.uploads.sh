#! /bin/bash

source ./adm/s.includes.sh

for LOC_PROJECT in "${A_LIST_PROJECTS[@]}"; do

    LOC_SOURCE="${A_L_BE}/${A_L_VAR_WWW}/${LOC_PROJECT}/uploads"
    LOC_TARGET="/osa/_bkp/alina/${LOC_PROJECT}/uploads"

    alina_rsync_local "${LOC_SOURCE}" "${LOC_TARGET}"

done