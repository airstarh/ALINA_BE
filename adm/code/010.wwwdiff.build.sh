#!/bin/bash

LOC_DIFF_DEFAULT="${A_L_BE}/${A_L_SRV}/${A_CONSUMERS}/${ALINA_DEFAULT_PROJECT}/.WwwDiff/"

for PROJECT in "${A_LIST_PROJECTS[@]}"; do

    LOC_DIFF_SOURCE="${A_L_BE}/${A_L_SRV}/${A_CONSUMERS}/${PROJECT}/.WwwDiff/"
    LCO_TARGET="${A_L_BE}/${A_L_VAR_WWW}/${PROJECT}/"

    alina_rsync_local "${LOC_DIFF_DEFAULT}" "${LOC_DIFF_SOURCE}" "${LCO_TARGET}"

done
