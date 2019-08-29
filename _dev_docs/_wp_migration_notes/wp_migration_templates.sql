SELECT message
FROM xf_search_index
WHERE
        `message` LIKE '%sixtyandme.com%'
   OR
        `message` LIKE '%sixtyandme.com%'
;

-- SELECT count(*)
SELECT message
FROM xf_search_index
WHERE
        `message` like '%//www.sixtyandme.com%'
   OR
        `message` LIKE '%//sixtyandme.com%'
;
#
update xf_search_index
set message = REPLACE(message, 'www.sixtyandme.com', 'www.stage.sixtyandme.com');
;
UPDATE xf_search_index
SET message = REPLACE(message, '//sixtyandme.com', '//stage.sixtyandme.com');
;
