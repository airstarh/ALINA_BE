#!/bin/bash

docker exec alina_mysql mysqldump \
  -u root \
  -p"${MYSQL_ROOT_PASSWORD}" \
  --databases stage \
  --add-drop-database \
  --add-drop-table \
  --complete-insert \
  --disable-keys \
  --single-transaction \
  --extended-insert=FALSE \
  --set-gtid-purged=OFF \
  | gzip > ./_GITOUT/db/stage.sql.gz
