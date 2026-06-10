#!/usr/bin/bash

for LOC_PROJECT in "${A_LIST_PROJECTS[@]}"; do

    echo ""
    echo ">>> ${LOC_PROJECT}"

    LOC_SOURCE="${A_L_BE}/${A_L_VAR_WWW}/${LOC_PROJECT}/"
    LOC_TARGET="${A_R_VAR_WWW}/${LOC_PROJECT}/"

    alina_rsync_ssh "${LOC_SOURCE}" "${LOC_TARGET}"

    echo "<<< ${LOC_PROJECT}"
    echo ""
done

echo "✅ ✅ ✅ ✅"
