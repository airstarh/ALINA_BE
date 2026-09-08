#! /bin/bash

LOC_TARGET="${A_L_BE}/${A_L_STORAGE}/db/"
LOC_SOURCE="${A_R_BE}/${A_R_STORAGE}/db/"
alina_rsync_from_remote "${LOC_TARGET}" "${LOC_SOURCE}"
