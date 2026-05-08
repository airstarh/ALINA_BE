#!/usr/bin/bash

REMOTE_TARGET="/var/www"

PROJECTS=(
    "stage"
    "saysimsim.ru"
    "m45a"
    "vov"
)

for PROJECT in "${PROJECTS[@]}"; do

    echo ">>> ${PROJECT}"

    SOURCE="${BE}/server/var/www/${PROJECT}/"
    TARGET="${REMOTE_TARGET}/${PROJECT}/"

    rsyncSsh "$SOURCE" "$TARGET"

    echo "<<< ${PROJECT}"
done

echo "✅ ✅ ✅ ✅"
