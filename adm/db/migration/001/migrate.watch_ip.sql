-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- 1. Индекс для скорости
ALTER TABLE
    watch_ip
ADD
    INDEX idx_temp (ip);

-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- 2. Удаление дубликатов — оптимально
DELETE t1
FROM
    watch_ip t1
    INNER JOIN watch_ip t2 ON t1.ip = t2.ip
    AND t2.id < t1.id;

-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
-- 3. Уникальное ограничение
ALTER TABLE
    watch_ip DROP INDEX idx_temp;

-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
ALTER TABLE
    watch_ip
ADD
    CONSTRAINT cns_uniq_watch_ip_ip UNIQUE USING BTREE (ip);
-- # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #