#!/bin/bash

source ../constfants

REMOTE_TARGET="/srv"

# Define array of projects
PROJECTS=(
    "alina"
    "alina_consumers"
)

# Loop through each project
for PROJECT in "${PROJECTS[@]}"; do
    echo ">>> ${PROJECT}"

    SOURCE="${BE}/server/srv/${PROJECT}/"
    TARGET="${REMOTE_TARGET}/${PROJECT}/"

    rsyncSsh "$SOURCE" "$TARGET"

    echo "<<< ${PROJECT}"
done

echo "✅ ✅"
