#! /bin/bash

alina_rsync_ssh_root() {

    local SOURCE="$1"
    local TARGET="$2"

    ssh "${REMOTE_ADDR}" "sudo mkdir -p -m 755 ${TARGET}"
    local ssh_status=$?

    if [[ $ssh_status -ne 0 ]]; then
        echo "Error: SSH mkdir failed (exit: $ssh_status)" >&2
        return $ssh_status
    fi

    rsync \
        -rltv \
        -z \
        --skip-compress=jpg,jpeg,png,gif,mp4,mp3,zip,gz,pdf \
        --delete-after \
        --filter='- **/cfg/db.php' \
        --filter='- **/cfg/mailer.php' \
        --filter='- **/uploads/' \
        --filter='P **/uploads/' \
        --filter='- ./database/' \
        --filter='P ./database/' \
        --filter='- ./,git/' \
        --filter='P ./.git/' \
        --filter='- ./_GITOUT/' \
        --filter='P ./_GITOUT/' \
        --filter='- ./.idea/' \
        --filter='P ./.idea/' \
        --filter='- ./.vscode/' \
        --filter='P ./.vscode/' \
        --filter='- ./nbproject/' \
        --filter='P ./nbproject/' \
        --filter='- ./nbproject/' \
        --filter='P ./nbproject/' \
        --dry-run
        -e "ssh" \
        --rsync-path="sudo rsync" \
        "${SOURCE}" \
        "${REMOTE_ADDR}:${TARGET}"

    local rsync_status=$?

    if [[ $rsync_status -ne 0 ]]; then
        echo "Error: sync failed (exit: $rsync_status)" >&2
    fi

    return $rsync_status
}
export alina_rsync_ssh_root