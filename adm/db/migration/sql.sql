ALTER TABLE watch_banned_visit ADD user_id BIGINT NULL;
CREATE INDEX watch_banned_visit_browser_enc_IDX USING BTREE ON watch_banned_visit (browser_enc,user_id,ip);
