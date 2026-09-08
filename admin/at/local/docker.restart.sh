#! /bin/bash

. ./admin/sss.inc.sh


. "$(asd)"/docker.down.sh
sleep 3
. "$(asd)"/docker.up.sh
