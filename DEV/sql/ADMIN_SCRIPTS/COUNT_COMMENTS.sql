SELECT 
a.`id`
, (SELECT COUNT(*) FROM tale AS sub WHERE sub.answer_to_tale_id = a.id) AS by_answer_to_tale_id
, (SELECT COUNT(*) FROM tale AS sub1 WHERE sub1.root_tale_id = a.id) AS by_root_tale_id
FROM tale a
WHERE 
a.`type` = 'POST'
AND 
a.id = 289
;

SELECT 
a.id
,a.`root_tale_id`
,a.`answer_to_tale_id`
,a.body
,a.`owner_id`
FROM tale a
WHERE 
(
a.id = 265
OR a.`answer_to_tale_id`= 265
OR a.`root_tale_id` = 265
)
;
SELECT 
a.id
,a.`root_tale_id`
,a.`answer_to_tale_id`
,a.body
,a.`owner_id`
FROM tale a
WHERE 
(
a.id = 278
OR a.`answer_to_tale_id`= 278
OR a.`root_tale_id` = 278
)
;

SELECT *
FROM tale a
WHERE a.body LIKE '%79f916cb4556775fe9703c4ad02b7d19%'
;
SELECT *
FROM tale a
WHERE a.body LIKE '%79f916cb4556775fe9703c4ad02b7d19%'
;