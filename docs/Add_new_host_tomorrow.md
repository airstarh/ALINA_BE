# Add a new host tomorrow

This guide adds another production host to the multi-host scripts in `admin/`.
The examples use profile `ccc`, project `example.home`, database `example`,
and remote user `sewa`. Replace them with the new host's real values.

## 1. Add an SSH alias

Edit `~/.ssh/config` on the local computer:

```sshconfig
Host ccc
    HostName actual-server.example.com
    User sewa
    IdentityFile ~/.ssh/ccc_key
```

The alias after `Host` should match the admin profile name. Verify it:

```bash
ssh ccc
```

Do not continue until the SSH connection works.

## 2. Create the admin profile

Copy an existing production profile:

```bash
cp admin/bin/config/host/bbb.sh admin/bin/config/host/ccc.sh
```

Edit `admin/bin/config/host/ccc.sh`:

```bash
#!/bin/bash

export ALINA_REMOTE_HOST="${CCC_REMOTE_HOST:-ccc}"
export ALINA_REMOTE_USER="${CCC_REMOTE_USER:-sewa}"
export ALINA_REMOTE_URL="${ALINA_REMOTE_USER}@${ALINA_REMOTE_HOST}"
export ALINA_REMOTE_SSH="${CCC_REMOTE_SSH:-/home/qqq/.ssh/ccc_key}"

export A_R_BE="/home/${ALINA_REMOTE_USER}/_A001/rep/ALINA_BE"
export A_R_VI="/home/${ALINA_REMOTE_USER}/_A001/rep/ALINA_V"
export A_R_SRV="server/srv"
export A_R_VAR_WWW="server/var/www"
export A_R_GITOUT="_GITOUT"
export A_R_STORAGE="${A_R_BE}/${A_STORAGE}"

export ALINA_BASES=("example")
export A_LIST_PROJECTS=("example.home")
export ALINA_DEFAULT_PROJECT="example.home"
```

For multiple databases or projects, use multiple array entries:

```bash
export ALINA_BASES=(
    "example"
    "example_archive"
)

export A_LIST_PROJECTS=(
    "example.home"
    "example-admin.home"
)
```
The profile must define the required values, particularly:

  ALINA_REMOTE_HOST
  ALINA_REMOTE_USER
  ALINA_REMOTE_URL
  ALINA_REMOTE_SSH

  A_R_BE
  A_R_VI
  A_R_SRV
  A_R_VAR_WWW
  A_R_GITOUT
  A_R_STORAGE

  ALINA_BASES
  A_LIST_PROJECTS
  ALINA_DEFAULT_PROJECT

  One exception remains: Docker commands require matching files under:

  admin/at/newhost/

  For ordinary deploy, SQL, and dynamic-file operations, the single host configuration file is sufficient.

## 3. Confirm automatic discovery

No dispatcher or test file needs to be edited. Every `*.sh` file in
`admin/bin/config/host/` is discovered automatically.

Confirm that the new profile appears:

```bash
bash admin/run.sh --help
```

## 4. Decide which commands the host supports

Code deployment, SQL transfer, and dynamic-file transfer reuse the shared
scripts and profile variables.

Docker commands currently support only `local` and `sss`. If `ccc` needs
Docker commands through the dispatcher, add suitable scripts under
`admin/at/ccc/` and invoke those scripts by their relative paths.

Do not point a new profile at `admin/at/sss/` blindly: production hosts may
require different Docker Compose files, permissions, ports, or services.

## 5. Run safe checks

No test or dispatcher edits are required. The existing test suite includes
a temporary future profile and proves automatic discovery.

These commands do not deploy, synchronize, back up, or restore anything:

```bash
bash admin/test/config.test.sh
bash admin/test/run.test.sh
shellcheck admin/bin/config/host/ccc.sh
```

Dry dispatch validates resolution without running the action:

```bash
ALINA_DRY_DISPATCH=1 bash admin/run.sh ccc bin/script/code/deploy.sh
ALINA_DRY_DISPATCH=1 bash admin/run.sh ccc bin/script/sql/backup.sh example
```

Expected output:

```text
profile=ccc script=bin/script/code/deploy.sh arguments=-
profile=ccc script=bin/script/sql/backup.sh arguments=example
```

## 6. Run a real operation only after review

After reviewing the profile and dry-dispatch output, run the required
operation explicitly. For example:

```bash
bash admin/run.sh ccc bin/script/code/deploy.sh
```

Real deploy, rsync, SQL backup, SQL restore, and Docker commands can modify
local or remote state. Dry dispatch and tests should always come first.
