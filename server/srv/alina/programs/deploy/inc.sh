#!/bin/bash

REMOTE_USER="sewa"
REMOTE_HOST="saysimsim.ru"
REMOTE_ADDR="${REMOTE_USER}@${REMOTE_HOST}"

rsyncSsh() {
        local SOURCE="$1"
        local TARGET="$2"

        ssh "${REMOTE_ADDR}" "sudo mkdir -p -m 775 ${TARGET}"

        rsync \
                -rltDv \
                --zstd \
                --compress-level=3 \
                --skip-compress=jpg/jpeg/png/gif/mp4/mp3/zip/gz/pdf \
                --delete-after \
                --filter='- **/cfg/db.php' \
                --filter='- **/cfg/mailer.php' \
                --filter='protect **/uploads/' \
                --filter='exclude **/uploads/' \
                -e \
                "ssh" \
                --rsync-path="sudo rsync" \
                "${SOURCE}" \
                "${REMOTE_ADDR}:${TARGET}"
}
