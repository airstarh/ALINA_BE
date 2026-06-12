#! /bin/bash

source ./adm/s.includes.sh

for LOC_PROJECT in "${ALINA_BASES[@]}"; do

    LOC_TARGET="${A_L_BE}/${A_L_GITOUT}/db/${LOC_PROJECT}.sql.gz"
    LOC_SOURCE="${A_R_BE}/${A_R_GITOUT}/db/${LOC_PROJECT}.sql.gz"

    alina_rsync_from_remote "${LOC_TARGET}" "${LOC_SOURCE}"

done