#! /bin/bash

. ./a.dev.perms.sh
. ./a.code.fix.sh
borg_git
. ./a.code.to.prod.sh
