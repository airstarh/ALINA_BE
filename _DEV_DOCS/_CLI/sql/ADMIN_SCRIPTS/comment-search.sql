SELECT id
     , root_tale_id
     , answer_to_tale_id
     , body_txt
FROM tale
WHERE id = 445
   OR root_tale_id = 445
   OR answer_to_tale_id = 445
;