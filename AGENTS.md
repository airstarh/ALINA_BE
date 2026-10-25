# Repository instructions

## Working agreement

- Never create a Git commit unless the user explicitly asks for one.
- Do not run a JavaScript SPA build without asking the user immediately beforehand.
- Before changing model persistence, inspect the model's `fields()`, `referencesTo()`, `hookRightBeforeSave()`, and `hookRightAfterSave()` implementations. Pass frontend model data through the established model save path instead of duplicating field filtering, relation synchronization, password processing, or other hook behavior in controllers. Missing fields must remain untouched; extra fields are restricted by the model and may be intentionally consumed by hooks.
- The user pre-authorizes read-only browser verification from the terminal with locally installed browsers in headless mode. This includes temporary browser profiles and bypassing local-development HTTPS certificate errors; no additional permission is needed for these checks.
- Preserve unrelated working-tree changes.
- Do not execute deploy, backup, restore, rsync, Docker, certificate, permission-changing, or other operational admin actions unless the user explicitly requests that operation. Static checks, dry dispatch, and `admin/test/*.test.sh` are safe.

## Admin command architecture

The canonical command format is:

```bash
bash admin/run.sh ENV path/to/script.sh arg1 arg2
```

Here, `ENV` is a profile such as `local`, `bbb`, or `sss`, and the script path is relative to `admin/`.

`admin/run.sh` validates the profile and script, then calls `alina_bootstrap`. The bootstrap loads:

- shared configuration;
- the selected environment profile from `admin/bin/config/host/`;
- arrays and exported variables;
- helper functions such as `asd` and `alina_sudo`.

The selected admin action is sourced in that prepared Bash process, and all trailing arguments are forwarded unchanged.

## Root `alina` command

The repository-root `alina` file is the short form of `bash admin/run.sh`:

```bash
./alina ENV path/to/script.sh arg1 arg2
```

It resolves the repository from its own file location, so execution does not depend on the current working directory.

The same file also provides per-terminal Bash initialization:

```bash
source ./alina
```

Sourcing it does not run an admin action. It loads `admin/bin/alina.completion.sh` and enables Bash Tab completion for:

- environment profiles in the first argument;
- admin-relative `.sh` paths in the second argument.

Completion is Bash-specific. Executing `./alina ...` still works when launched from another shell because the script itself uses Bash.

## `alina_sudo`

Use `alina_sudo` inside an action already launched through `admin/run.sh` or `./alina` when a command must run as root while retaining the active ALINA context.

`alina_sudo` preserves the caller's `HOME`, `SSH_AUTH_SOCK`, current profile name, profile directory, and admin directory. Inside the privileged Bash process it runs `alina_bootstrap` again for the current profile before executing the requested command. This restores the profile variables, arrays, and helper functions under sudo.

Prefer direct execution of an admin script rather than launching a nested `admin/run.sh`:

```bash
alina_sudo "${ALINA_ADMIN}/at/sss/perms.sh"
```

For a script in the same directory as the caller, this is also valid:

```bash
alina_sudo "$(asd)/perms.sh"
```

`$(asd)` is evaluated by the current action before `alina_sudo` is called, so the privileged command receives an already-resolved absolute path.

Do not load a different profile merely because a target script is stored under another profile-named directory. For example, an action running with the `bbb` profile may directly execute `admin/at/sss/perms.sh` through `alina_sudo` while retaining the active `bbb` configuration.

## Important files

- `alina`: executable command wrapper and sourceable shell initializer.
- `admin/run.sh`: canonical profile-aware dispatcher.
- `admin/bin/bootstrap.sh`: loads the complete ALINA environment.
- `admin/bin/function/alina_sudo.sh`: privileged command wrapper.
- `admin/bin/alina.completion.sh`: Bash completion implementation.
- `admin/bin/config/host/*.sh`: environment profiles discovered automatically.
- `admin/do/**/*.sh`: shared actions.
- `admin/at/<profile>/*.sh`: environment/host-oriented actions.
- `admin/test/*.test.sh`: safe admin regression tests.

## Safe verification

Run all admin regression tests with:

```bash
for test_file in admin/test/*.test.sh; do
    bash "$test_file" || exit
done
```

Also use `bash -n`, ShellCheck, and `git diff --check` for affected shell files. These checks must not be replaced by executing operational actions.
