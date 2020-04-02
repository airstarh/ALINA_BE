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

SELECT 
a.id
,a.`user_id`
,a.`ip`
, FROM_UNIXTIME(a.`expires_at`) AS EXPIRES
, FROM_UNIXTIME(a.`lastentered`) AS LE
,u.`firstname`
FROM login a
LEFT JOIN `user` u ON u.id = a.user_id
WHERE 1
ORDER BY LE DESC
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
-- ORDER by a.id DESC
ORDER BY b.ip
;

SELECT COUNT(*) AS total
FROM watch_visit
WHERE method='POST'
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
COUNT(*) AS total
-- *
FROM watch_url_path
WHERE 1
ORDER BY 
`visits` DESC
,`url_path` ASC
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
ORDER BY 
`visits` DESC
,`ip` ASC
;