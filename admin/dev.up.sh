#! /bin/bash

DIR_THIS="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

cd "${DIR_THIS}/.." || exit
# bash ./persms.dev.sh
docker compose -f dc.yml up -d