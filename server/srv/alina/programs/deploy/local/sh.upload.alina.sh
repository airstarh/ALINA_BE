#!/usr/bin/bash

REMOTE_USER="sewa"
REMOTE_HOST="saysimsim.ru"
REMOTE_ADDR="${REMOTE_USER}@${REMOTE_HOST}"
REMOTE_TARGET="/srv"

# Define array of projects
PROJECTS=(
    "alina"
    "alina_consumers"
)

# Loop through each project
for PROJECT in "${PROJECTS[@]}"; do
    echo "=== Processing project: ${PROJECT} ==="
    
    # Define source and target paths
    SOURCE="/home/qqq/a/b/server/srv/${PROJECT}/"
    TARGET="${REMOTE_TARGET}/${PROJECT}/"
    
    # Sync files
    rsync \
        -avz \
        --no-perms --no-owner --no-group \
        --delete-after \
        --filter='- **/cfg/db.php' \
        --filter='- **/cfg/mailer.php' \
         -e \
         "ssh" \
         --rsync-path=" \
         sudo mkdir -p -m 775 ${TARGET} \
         && sudo rsync --no-perms --no-owner --no-group \
         " \
         "${SOURCE}" \
         "${REMOTE_ADDR}:${TARGET}"
    
    echo "✅ Completed: ${PROJECT}"
done

echo "✅ ✅ ✅ All projects synced successfully!"
