# Admin multi-host command design

## Goal

Provide one consistent admin entry point for local work and multiple production hosts while keeping every reusable function and action in its own readable file.

## Command interface

Commands use `bash admin/run.sh <profile> <area> <action> [target]`.

Examples include `bash admin/run.sh sss code deploy`, `bash admin/run.sh bbb code deploy`, `bash admin/run.sh sss sql backup zero`, and `bash admin/run.sh local sql restore zero`.

The dispatcher validates the profile, command, and required target before loading an action. It is the only supported entry point.

## Configuration

- `admin/bin/config/common.sh` contains shared local paths, storage paths, and infrastructure defaults.
- `admin/bin/config/host/sss.sh` contains the existing sss remote host, projects, and databases.
- `admin/bin/config/host/bbb.sh` uses SSH alias `bbb`, project `borg.home`, and database `borg`.
- `admin/bin/bootstrap.sh` loads strict Bash settings, environment secrets, common configuration, the selected profile, and shared functions.
- Operational scripts receive their selected profile from `admin/run.sh` and never load a profile themselves.

The real address for `bbb` stays in the user's SSH configuration. Repository scripts refer only to the stable alias.

## Code organization

Files in `admin/bin/function/` and `admin/bin/script/` remain separate. `admin/run.sh` only parses arguments, bootstraps a profile, and dispatches to an existing action. Profile files use the current variable names so existing actions can be reused.

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
