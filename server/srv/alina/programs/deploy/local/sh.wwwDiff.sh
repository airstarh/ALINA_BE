#!/usr/bin/bash

BASE="zero.home"
SOURCE_BASE="/home/qqq/a/b/server/srv/alina_consumers/${BASE}/.wwwDiff/"

PROJECT="stage"
TARGET="/home/qqq/a/b/server/var/www/${PROJECT}/"
SOURCE_DIFF="/home/qqq/a/b/server/src/alina_consumers/${PROJECT}/.wwwDiff/"

rsync \
    -av \
    --exclude='uploads' \
    --exclude='apps' \
    "${SOURCE_BASE}" "${TARGET}"

rsync \
    -av \
    --exclude='uploads' \
    --exclude='apps' \
    "${SOURCE_DIFF}" "${TARGET}"