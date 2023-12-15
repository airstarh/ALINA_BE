-- CREATE INDEX DESC
CREATE INDEX audit_at_IDX USING BTREE ON `home.zero`.audit (`at` DESC);

-- ALTER KEY
ALTER TABLE `home.zero`.tale DROP INDEX IND_TALE_PUBLISH_AT;
ALTER TABLE `home.zero`.tale DROP INDEX IND_TALE_CREATED_AT;
CREATE INDEX tale_publish_at_IDX USING BTREE ON `home.zero`.tale (publish_at DESC);