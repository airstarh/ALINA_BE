#!/usr/bin/bash

REMOTE_USER="sewa"
REMOTE_HOST="saysimsim.ru"
REMOTE_ADDR="${REMOTE_USER}@${REMOTE_HOST}"

# Define array of projects
PROJECTS=(
    "stage"
    "saysimsim.ru"
    "m45a"
    "vov"
)

# Loop through each project
for PROJECT in "${PROJECTS[@]}"; do
    echo "=== Processing project: ${PROJECT} ==="
    
    # Define source and target paths
    SOURCE="/home/qqq/a/b/server/var/www/${PROJECT}/apps/vue/"
    TARGET="/var/www/${PROJECT}/apps/vue"
    
    # Remove remote directory with sudo
    ssh "${REMOTE_ADDR}" "sudo rm -rf '${TARGET}'"
    
    # Sync files
    rsync -avz -e "ssh" "${SOURCE}" "${REMOTE_ADDR}:${TARGET}"
    
    echo "✅ Completed: ${PROJECT}"
done

echo "✅ ✅ ✅ All projects synced successfully!"
