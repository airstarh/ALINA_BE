SELECT
-- a.id
-- , a.user_id
    FROM_UNIXTIME(a.`visited_at`) AS at
     -- , a.`method`
     , a.`ip`
     , a.`query_string`
     , a.`data`
     , a.`method`
FROM alina.`watch_visit` a
WHERE 1
  AND a.ip != '91.202.25.124'
--  AND a.user_id IS NULL
  AND (
        a.query_string LIKE '%tale/upsert/512%' /*SERIALIZER*/
        OR
        a.query_string LIKE '%tale/upsert/523%' /*JSON*/
    )
  AND a.method != 'GET'
ORDER BY a.visited_at DESC
-- ORDER BY b.ip
;