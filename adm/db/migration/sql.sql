ALTER TABLE stage.watch_visit ADD error_class varchar(500) NULL;
ALTER TABLE stage.watch_visit ADD error_severity VARCHAR(100);
ALTER TABLE stage.watch_visit ADD error_code INTEGER NULL;
ALTER TABLE stage.watch_visit ADD error_file varchar(500) NULL;
ALTER TABLE stage.watch_visit ADD error_line INTEGER NULL;
ALTER TABLE stage.watch_visit ADD error_trace LONGTEXT NULL;
ALTER TABLE stage.watch_visit ADD error_text TEXT NULL;
ALTER TABLE stage.watch_visit ADD referal varchar(500) NULL;
ALTER TABLE stage.watch_visit ADD ban_point TINYINT NULL;
