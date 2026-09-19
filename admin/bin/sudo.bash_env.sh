#!/usr/bin/env bash

# Recreate the runner's project-local context for non-interactive Bash
# processes started through the sudo wrapper in admin/run.sh.
source "$ALINA_ADMIN/bin/bootstrap.sh"
alina_bootstrap "$ALINA_PROFILE"
