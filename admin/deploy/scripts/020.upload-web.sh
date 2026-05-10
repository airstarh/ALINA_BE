#!/usr/bin/bash

for LOC_PROJECT in "${ALINA_PROJECTS[@]}"; do

        echo ""
    echo ">>> ${LOC_PROJECT}"

    LOC_SOURCE="${BE}/server/var/www/${LOC_PROJECT}/"
    LOC_TARGET="${ALINA_FE_REMOTE_DIR}/${LOC_PROJECT}/"

    rsyncSsh "$LOC_SOURCE" "$LOC_TARGET"

    echo "<<< ${LOC_PROJECT}"
    echo ""
done

echo "✅ ✅ ✅ ✅"
