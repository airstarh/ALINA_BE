#!/bin/bash


for LOC_PROJECT in "${ALINA_PROJECTS[@]}"; do

    echo ""
    echo ">>> ${LOC_PROJECT}"

    LOC_SOURCE="${BE}/${ALINA_BE_DOCKER}/${LOC_PROJECT}/"
    LOC_TARGET="${ALINA_BE_REMOTE_DIR}/${LOC_PROJECT}/"

    rsyncSsh "${LOC_SOURCE}" "${LOC_TARGET}"

    echo "<<< ${LOC_PROJECT}"
    echo ""
done

echo "✅ ✅ ✅ ✅"
