#!/bin/bash


for LOC_PROJECT in "${ALINA_PROJECTS[@]}"; do

    echo ""
    echo ">>> ${LOC_PROJECT}"

    LOC_SOURCE="${ALINA_BE_LOCAL_DIR}/${ALINA_DOCKER_VOL_FE}/${LOC_PROJECT}/"
    LOC_TARGET="${ALINA_BE_REMOTE_DIR}/${LOC_PROJECT}/"

    alina_rsync_ssh "${LOC_SOURCE}" "${LOC_TARGET}"

    echo "<<< ${LOC_PROJECT}"
    echo ""
done

echo "✅ ✅ ✅ ✅"
