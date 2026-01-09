#!/usr/bin/bash

REMOTE_USER="sewa"
REMOTE_HOST="saysimsim.ru"
REMOTE_ADDR="${REMOTE_USER}@${REMOTE_HOST}"
REMOTE_TARGET="/var/www"

# Define array of projects
PROJECTS=(
    "stage"
    # "saysimsim.ru"
    # "m45a"
    # "vov"
)

ALINA="/home/qqq/a/b/server/srv/alina"
ALINA_CONSUMERS="/home/qqq/a/b/server/srv/alina_consumers"

# Loop through each project
for PROJECT in "${PROJECTS[@]}"; do
    echo "=== Processing project: ${PROJECT} ==="
    
    # Define source and target paths
    SOURCE="/home/qqq/a/b/server/var/www/${PROJECT}/"
    TARGET="${REMOTE_TARGET}/${PROJECT}/"
    
    # Sync files
    rsync \
        -avz \
        --filter='P uploads/' \
        --filter='H uploads/' \
         -e \
         "ssh" \
         --rsync-path="sudo mkdir -p ${TARGET} && sudo rsync" \
         "${SOURCE}" \
         "${REMOTE_ADDR}:${TARGET}"
    
    echo "✅ Completed: ${PROJECT}"
done

echo "✅ ✅ ✅ All projects synced successfully!"
