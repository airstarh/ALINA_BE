#!/bin/bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

fail() {
    echo "FAIL: $*" >&2
    exit 1
}

for removed_file in \
    admin/sss.inc.sh \
    admin/bin/config/sss.sh \
    admin/code/compile/sss.run.sh \
    admin/code/deploy/sss.upl.sh \
    admin/dyn/backup/sss.uploads.dwl.sh \
    admin/dyn/restore/sss.uploads.upl.sh \
    admin/sql/backup/sss.vov.run.sh \
    admin/sql/backup/sss.zero.run.sh \
    admin/sql/restore/local.m45a.run.sh \
    admin/sql/restore/local.vov.run.sh \
    admin/sql/restore/local.zero.sh \
    admin/sql/migrate/run.sh
do
    [[ ! -e "$ROOT_DIR/$removed_file" ]] || fail "$removed_file still exists"
done

if rg -n 'sss\.inc\.sh|bin/config/sss\.sh' "$ROOT_DIR/admin" -g '*.sh' -g '!*.test.sh' >/dev/null; then
    fail "admin scripts still reference the compatibility configuration"
fi

echo "PASS: run.sh-only architecture"
