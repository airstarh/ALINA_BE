#!/bin/bash

ALINA_CONFIG_DIR="${BASH_SOURCE[0]%/*}"
ALINA_ADMIN="${ALINA_ADMIN:-$(cd "${ALINA_CONFIG_DIR}/../.." && pwd)}"
ALINA_ROOT="${ALINA_ROOT:-$(cd "${ALINA_ADMIN}/.." && pwd)}"

export ALINA_ADMIN
export ALINA_ROOT

source "${ALINA_CONFIG_DIR}/common.sh"
source "${ALINA_CONFIG_DIR}/host/sss.sh"
