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
  -- AND a.user_id IS NULL
  AND a.query_string LIKE '%auth/login%'
  AND b.ip != '91.202.25.124'
-- AND b.`visits` > 10
-- ORDER by a.id DESC
-- ORDER BY b.ip
-- GROUP BY b.ip
-- ORDER BY ip_VISITS_TOTAL DESC
ORDER BY a.visited_at DESC
-- ORDER BY b.ip
;

SELECT *
FROM wa
WHERE 1
;