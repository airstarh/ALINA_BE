#! /bin/bash

source ./adm/s.includes.sh

LOC_TARGET="${A_L_BE}/${A_L_GITOUT}/db/"
LOC_SOURCE="${A_R_BE}/${A_R_GITOUT}/db/"
alina_rsync_from_remote "${LOC_TARGET}" "${LOC_SOURCE}"