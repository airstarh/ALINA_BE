#! /bin/bash

LOC_SOURCE="${A_R_STORAGE}/${SUB_SQL}/"
LOC_TARGET="${A_L_STORAGE}/${SUB_SQL}/"
alina_rsync_from_remote "${LOC_TARGET}" "${LOC_SOURCE}"
