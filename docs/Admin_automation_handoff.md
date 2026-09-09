# Admin automation handoff

Last updated: 2026-09-09

## Working agreement

- Never run `git commit` unless the user explicitly asks for that exact operation.
- The user normally reviews and commits all project changes.
- Preserve unrelated working-tree changes.
- Do not execute backup, restore, deploy, rsync, Docker, certificate, or other operational scripts unless the user explicitly authorizes the operation.
- Static checks such as `bash -n`, ShellCheck, dry dispatch, and the tests in `admin/test/` are safe.

## Current architecture

The only supported entry point is:

```bash
bash admin/run.sh <profile> <script-relative-to-admin> [script arguments]
```

Examples:

```bash
bash admin/run.sh sss at/sss/docker.up.sh
bash admin/run.sh bbb do/code/deploy.sh
bash admin/run.sh sss do/sql/backup.sh zero
bash admin/run.sh local do/sql/restore.sh zero
```

`admin/run.sh` performs these steps:

1. Discovers profiles from `admin/bin/config/host/*.sh`.
2. Validates the selected profile and relative `.sh` path.
3. Sources `admin/bin/bootstrap.sh`.
4. Loads common configuration, the selected host configuration, and separate function files.
5. Sources the selected action in the same Bash process.
6. Passes every argument after the script path through as `$1`, `$2`, and `"$@"`.

Dry validation does not execute the selected action:

```bash
ALINA_DRY_DISPATCH=1 bash admin/run.sh bbb do/code/deploy.sh
```

## Adding a host

For the standard shared actions, add one file:

```text
admin/bin/config/host/<profile>.sh
```

The profile is discovered automatically and appears in `bash admin/run.sh --help`. See `docs/Add_new_host_tomorrow.md` for the complete procedure.

Docker is the exception: a host also needs matching scripts under `admin/at/<profile>/` if it needs host-specific Docker actions.

## Directory responsibilities

- `admin/bin/bootstrap.sh`: configuration/function loader.
- `admin/bin/config/common.sh`: shared values.
- `admin/bin/config/host/*.sh`: one configuration file per profile.
- `admin/bin/function/*.sh`: separate reusable Bash functions; keep them as separate files for readability.
- `admin/do/code/*.sh`: shared code actions.
- `admin/do/dyn/*.sh`: shared dynamic-file actions.
- `admin/do/sql/*.sh`: shared SQL actions.
- `admin/at/<profile>/*.sh`: host/environment-specific actions.
- `admin/test/*.sh`: non-operational architecture/configuration/runner tests.

Legacy `admin/sss.inc.sh`, `admin/bin/config/sss.sh`, legacy launchers, and `admin/bin/script/` were removed intentionally.

## SQL backup findings already resolved

- MySQL 8 rejected `SET SESSION max_allowed_packet`; that statement was removed.
- Missing host-side `pv` previously caused exit 127 and an empty dump.
- Backup now uses `pv` when available and `cat` as a transparent fallback.
- `pv`, `gzip`, and output redirection run on the host; `mysqldump` runs inside `alina_mysql`.
- A successful real `zero` backup and gzip integrity check were performed with explicit user authorization.

## Safe verification

```bash
bash admin/test/architecture.test.sh
bash admin/test/config.test.sh
bash admin/test/run.test.sh

find admin -type f -name '*.sh' -size +0c -print0 \
    | xargs -0 -n1 bash -n

shellcheck admin/run.sh admin/bin/bootstrap.sh admin/test/*.sh admin/test/support/*.sh
git diff --check
```

These checks do not run operational action scripts.

## Suggested future work

Work on only one item at a time:

1. Make SQL backup replacement atomic: write a temporary archive, validate it, and rename only after success.
2. Move Docker Compose file arrays into profiles and create shared Docker actions.
3. Add profile capability declarations.
4. Add a read-only profile `check` action.
5. Validate the real `bbb` SSH alias, paths, database, and project before its first real operation.

## Current repository state

The admin action-directory move is committed at `126ce5bb` (`dev do`). At the time this handoff was written, the only pre-existing working-tree change was:

```text
M server/etc/nginx/conf.d/default.conf
```

That nginx change belongs to the user and must not be modified as part of admin work.

## Fast resume prompt

Open this repository and conversation if available, then say:

> Read `docs/Admin_automation_handoff.md`, inspect current Git status, and continue the admin work from there. Do not commit anything. Preserve unrelated changes and do not execute operational scripts without my explicit permission.

