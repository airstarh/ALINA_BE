#!/bin/bash


for LOC_PROJECT in "${A_LIST_CORE[@]}"; do

    echo ""
    echo ">>> ${LOC_PROJECT}"

    LOC_SOURCE="${A_BE}/${A_L_SERVER_SRV}/${LOC_PROJECT}/"
    LOC_TARGET="${A_R_SRV}/${LOC_PROJECT}/"

    alina_rsync_ssh "${LOC_SOURCE}" "${LOC_TARGET}"

    echo "<<< ${LOC_PROJECT}"
    echo ""
done

echo "✅ ✅ ✅ ✅"
