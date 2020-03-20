SELECT *
FROM error_log
WHERE 1
ORDER BY id DESC
;

DELETE FROM error_log
WHERE ip='91.202.25.124'
;

DELETE FROM watch_visit
WHERE ip='91.202.25.124'
;

SELECT *
FROM login
WHERE 1
ORDER BY id DESC
;

SELECT 
a.id
, a.user_id
, a.`query_string`
, a.`data`
, b.`ip`
, c.user_agent
FROM `watch_visit` a
LEFT JOIN watch_ip b ON  a.`ip_id` = b.`id`
LEFT JOIN watch_browser c ON  a.`browser_id` = c.`id`
WHERE a.method='POST'
ORDER BY a.id DESC
;

SELECT *
FROM `user`
WHERE 1
;

SELECT 
a.*
, b.`ip`
, c.user_agent
FROM watch_banned_visit a
LEFT JOIN watch_ip b ON  a.`ip_id` = b.`id`
LEFT JOIN watch_browser c ON  a.`browser_id` = c.`id`
WHERE 1
;

SELECT 
-- count(*) as total,
*
FROM watch_url_path
WHERE 1
ORDER BY 
`visits` DESC
,`url_path` ASC
;
SELECT *
FROM watch_browser
WHERE 1
;

SELECT 
-- count(*) as total
*
FROM watch_ip
WHERE 1
ORDER BY 
`visits` DESC
,`ip` ASC
;