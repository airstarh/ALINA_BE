#!/bin/bash

for db in "${ALINA_BASES[@]}"; do
  docker exec alina_mysql mysqldump \
    -u root \
    -p"${MYSQL_ROOT_PASSWORD}" \
    --databases "$db" \
    --add-drop-database \
    --add-drop-table \
    --complete-insert \
    --disable-keys \
    --single-transaction \
    --extended-insert=FALSE \
    --set-gtid-purged=OFF \
    | gzip > "./${A_R_GITOUT}/db/${db}.sql.gz"
done
