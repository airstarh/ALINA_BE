# Admin multi-host command design

## Goal

Provide one consistent admin entry point for local work and multiple production hosts while keeping every reusable function and action in its own readable file.

## Command interface

Commands use `bash admin/run.sh <profile> <script-relative-to-admin> [script arguments]`.

Examples include `bash admin/run.sh sss at/sss/docker.up.sh`, `bash admin/run.sh bbb do/code/deploy.sh`, and `bash admin/run.sh sss do/sql/backup.sh zero`.

The runner validates the profile and script path, loads configuration, then sources the selected script with its remaining arguments. It is the only supported entry point.
Host profiles are discovered from `admin/bin/config/host/*.sh`; adding a profile does not require a dispatcher change.

## Configuration

- `admin/bin/config/common.sh` contains shared local paths, storage paths, and infrastructure defaults.
- `admin/bin/config/host/sss.sh` contains the existing sss remote host, projects, and databases.
- `admin/bin/config/host/bbb.sh` uses SSH alias `bbb`, project `borg.home`, and database `borg`.
- `admin/bin/bootstrap.sh` loads strict Bash settings, environment secrets, common configuration, the selected profile, and shared functions.
- Operational scripts receive their selected profile from `admin/run.sh` and never load a profile themselves.

The real address for `bbb` stays in the user's SSH configuration. Repository scripts refer only to the stable alias.

## Code organization

Files in `admin/bin/function/` and `admin/do/` remain separate. `admin/run.sh` has no command routing table: it bootstraps a profile and sources the requested script path. Profile files use the current variable names so existing actions can be reused.

## Dispatch scope

The first implementation covers code compile/deploy; SQL backup/restore/download/migrate; dynamic uploads backup/restore; and Docker build/config/up/down/restart. Certificate and permission scripts remain directly callable because they have special privilege and host-specific behavior.

## Compatibility and safety

- Operational commands run only through `bash admin/run.sh ...`.
- Scripts do not need executable bits.
- The dispatcher changes to the repository root before loading helpers.
- Invalid profiles and commands fail before any operational script runs.
- Static and validation tests do not invoke destructive, remote, Docker, or database actions.

## Verification

Run `bash -n` and ShellCheck over non-empty admin shell files. Exercise help, invalid-command handling, configuration loading, and the absence of compatibility launchers without running operational actions.
