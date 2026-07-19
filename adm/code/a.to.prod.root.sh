#!/bin/bash

source ./adm/s.includes.sh

SOURCE="${A_L_BE}"
TARGET="${A_R_BE}"

alina_rsync_to_remote "${SOURCE}" "${TARGET}"

SOURCE="${A_L_VI}/020.d"
TARGET="${A_R_VI}/020.d"

alina_rsync_to_remote "${SOURCE}" "${TARGET}"
