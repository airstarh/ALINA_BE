SELECT a.id
     , a.`user_id`
     , a.`ip`
     , FROM_UNIXTIME(a.`expires_at`)  AS EXPIRES
     , FROM_UNIXTIME(a.`lastentered`) AS LE
     , u.`firstname`
FROM login a
         LEFT JOIN `user` u ON u.id = a.user_id
WHERE 1
ORDER BY LE DESC
;

SELECT
-- a.id
-- , a.user_id
    FROM_UNIXTIME(a.`visited_at`) AS at
     -- , a.`method`
     , a.`ip_id`
     , b.`ip`
     , a.`query_string`
     , a.`data`
     , b.`visits`                 AS ip_VISITS_TOTAL
     , c.user_agent
FROM alina.`watch_visit` a
         LEFT JOIN watch_ip b ON a.`ip_id` = b.`id`
         LEFT JOIN watch_browser c ON a.`browser_id` = c.`id`
WHERE a.method != 'GET'
  AND a.user_id IS NULL
  AND a.query_string NOT LIKE '%auth/login%'
  AND b.ip != '91.202.25.124'
  AND b.`visits` > 10
-- ORDER by a.id DESC
-- ORDER BY b.ip
-- GROUP BY b.ip
-- ORDER BY ip_VISITS_TOTAL DESC
ORDER BY a.visited_at DESC
-- ORDER BY b.ip
;
DELETE
FROM watch_visit
where ip_id = 845;

SELECT COUNT(*) AS total
FROM watch_visit
WHERE method = 'POST'
;

SELECT *
FROM `user`
WHERE 1
;

SELECT a.*
     , b.`ip`
     , c.user_agent
FROM watch_banned_visit a
         LEFT JOIN watch_ip b ON a.`ip_id` = b.`id`
         LEFT JOIN watch_browser c ON a.`browser_id` = c.`id`
WHERE 1
;

SELECT
-- COUNT(*) AS total
*
FROM watch_url_path
WHERE url_path NOT LIKE '/tale/feed%'
  AND url_path NOT LIKE '/auth/login'
  AND url_path NOT LIKE '/auth/profile%'
  AND url_path NOT LIKE '/tale/upsert%'
  AND url_path NOT LIKE '/FileUpload/CkEditor'
  AND url_path NOT LIKE '/FileUpload/Common'
  AND url_path NOT LIKE '/notification/SelectListLatest%'
  AND url_path NOT LIKE '/like/process'
ORDER BY `visits` DESC
       , `url_path` ASC
;
SELECT *
FROM watch_browser
WHERE 1
ORDER BY visits DESC
;

SELECT
-- count(*) as total
*
FROM watch_ip
WHERE 1
ORDER BY `visits` DESC
       , `ip` ASC
;

SELECT *
FROM `user`
WHERE 1
;
SELECT id,
       ip,
       method,
       browser,
       header,
       `get`,
       post,
       file,
       created_at
FROM alina.watch_fools
WHERE 1
order by id desc
;