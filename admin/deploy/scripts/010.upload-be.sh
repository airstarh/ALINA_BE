#!/bin/bash

REMOTE_BASE_FOLDER="/srv"

# Define array of projects
ALINA_BE_FOLDERS=(
    "alina"
    "alina_consumers"
)

# Loop through each project
for BE_FOLDER in "${ALINA_BE_FOLDERS[@]}"; do
    echo ""
    echo ">>> ${BE_FOLDER}"

    SOURCE_ALINA_BE_FOLDER="${BE}/server/srv/${BE_FOLDER}/"
    TARGET_ALINA_BE_FOLDER="${REMOTE_BASE_FOLDER}/${BE_FOLDER}/"

    rsyncSsh "${SOURCE_ALINA_BE_FOLDER}" "${TARGET_ALINA_BE_FOLDER}"

    echo "<<< ${BE_FOLDER}"
    echo ""
done

echo "✅ ✅"
