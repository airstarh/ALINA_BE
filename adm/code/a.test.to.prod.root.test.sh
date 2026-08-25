#!/bin/bash

. ./adm/s.includes.sh

SOURCE="${A_L_BE}"
TARGET="/tmp/test_1"
alina_rsync_to_remote "${SOURCE}" "${TARGET}"

# SOURCE="${A_L_VI}/020.d"
# TARGET="/tmp/test_1"
# alina_rsync_to_remote "${SOURCE}" "${TARGET}"
