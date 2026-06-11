#!/bin/bash

source ./adm/s.includes.sh

SOURCE="${A_L_BE}"
TARGET="${A_R_ROOT}"

alina_rsync_to_remote "${SOURCE}" "${TARGET}"