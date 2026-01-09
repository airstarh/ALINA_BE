#!/usr/bin/bash

REMOTE_USER="sewa"
REMOTE_HOST="saysimsim.ru"
REMOTE_ADDR="${REMOTE_USER}@${REMOTE_HOST}"
REMOTE_TARGET="/tmp/diff"

# Define array of projects
PROJECTS=(
    "alina"
    "alina_consumers"
)

ALINA="/home/qqq/a/b/server/srv/alina"
ALINA_CONSUMERS="/home/qqq/a/b/server/srv/alina_consumers"

# Loop through each project
for PROJECT in "${PROJECTS[@]}"; do
    echo "=== Processing project: ${PROJECT} ==="
    
    # Define source and target paths
    SOURCE="/home/qqq/a/b/server/srv/${PROJECT}/"
    TARGET="${REMOTE_TARGET}/${PROJECT}/"
    
    # Sync files
    rsync \
        -avz \
        --delete-after \
        --filter='P cfg/' \
        --filter='H cfg/' \
         -e \
         "ssh" \
         --rsync-path="sudo mkdir -p ${TARGET} && sudo rsync" \
         "${SOURCE}" \
         "${REMOTE_ADDR}:${TARGET}"
    
    echo "✅ Completed: ${PROJECT}"
done

echo "✅ ✅ ✅ All projects synced successfully!"
