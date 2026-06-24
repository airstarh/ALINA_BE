DELETE FROM voc
WHERE id NOT IN (
  SELECT min_id
  FROM (
    SELECT MA(id) AS min_id
    FROM voc
    GROUP BY `from`
  ) AS subquery
);
ALTER TABLE voc MODIFY COLUMN `from` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL;
ALTER TABLE voc MODIFY COLUMN en_US VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE voc MODIFY COLUMN ru_RU VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE voc ADD CONSTRAINT cns_voc_from UNIQUE KEY (`from`);

ALTER TABLE watch_banned_visit ADD user_id BIGINT NULL;
CREATE INDEX watch_banned_visit_browser_enc_IDX USING BTREE ON watch_banned_visit (browser_enc,user_id,ip);
