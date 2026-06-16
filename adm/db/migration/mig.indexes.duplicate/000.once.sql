-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
ALTER TABLE router_alias MODIFY COLUMN alias varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE stage.router_alias MODIFY COLUMN url varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
DROP TABLE watch_url_path;
DROP TABLE watch_ip;
