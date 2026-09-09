# Admin Multi-Host Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Add a profile-aware admin dispatcher for local, sss, and bbb operations without combining existing function or action files.

**Architecture:** admin/run.sh is the only entry point. It validates a profile and relative script path, loads common configuration and shared functions, then sources that script with its remaining arguments; action files never select profiles.

**Tech Stack:** Bash, ShellCheck

**Spec:** docs/superpowers/specs/2026-09-09-admin-multi-host-design.md

## Global Constraints

- Operational commands run only through bash admin/run.sh.
- Scripts do not need executable bits.
- Function and action implementations remain separate files.
- Invalid profiles and commands fail before operational scripts run.
- Tests must not invoke remote, Docker, database, deployment, or synchronization actions.
- Do not commit implementation changes; the user will review and commit.

---

### Task 1: Configuration profiles and bootstrap

**Files:**
- Create: admin/bin/config/common.sh
- Create: admin/bin/config/host/local.sh
- Create: admin/bin/config/host/sss.sh
- Create: admin/bin/config/host/bbb.sh
- Create: admin/bin/bootstrap.sh
- Delete: admin/sss.inc.sh
- Delete: admin/bin/config/sss.sh
- Test: admin/test/config.test.sh

**Interfaces:**
- alina_bootstrap PROFILE loads environment, common config, one profile, and shared functions.
- Profile files define remote values, ALINA_BASES, A_LIST_PROJECTS, and ALINA_DEFAULT_PROJECT.
- Operational action files depend on configuration already loaded by admin/run.sh.

- [ ] Write a config test that sources each profile in a clean Bash process, asserts profile/project/database values, and fails because bootstrap does not exist.
- [ ] Run bash admin/test/config.test.sh and confirm the missing-bootstrap failure.
- [ ] Split current configuration, implement alina_bootstrap, and remove legacy sss loading.
- [ ] Run the config test, Bash parser, and ShellCheck.

### Task 2: Safe dispatcher and command mapping

**Files:**
- Create: admin/run.sh
- Test: admin/test/run.test.sh

**Interfaces:**
- Command: bash admin/run.sh PROFILE SCRIPT_PATH [SCRIPT_ARGUMENTS].
- ALINA_DRY_DISPATCH=1 validates and prints the resolved action without sourcing it.
- Supported actions: code compile/deploy; sql backup/restore/download/migrate; dyn backup/restore; docker build/config/up/down/restart.

- [ ] Write dispatcher tests for help, invalid profiles, missing SQL targets, profile resolution, and dry dispatch.
- [ ] Run bash admin/test/run.test.sh and confirm failure because admin/run.sh does not exist.
- [ ] Implement validation, target variables, and action dispatch.
- [ ] Run dispatcher/config tests, Bash parser, and ShellCheck.

### Task 3: Repository-wide single-entry verification

**Files:**
- Verify: every non-empty shell file under admin.

**Interfaces:**
- Legacy launchers and compatibility configuration are absent.
- New commands resolve paths without invoking actions when ALINA_DRY_DISPATCH=1.

- [ ] Run both admin test scripts.
- [ ] Run Bash parsing over every non-empty admin shell file.
- [ ] Run ShellCheck over new and modified shell files.
- [ ] Run git diff --check and inspect the complete uncommitted diff.
