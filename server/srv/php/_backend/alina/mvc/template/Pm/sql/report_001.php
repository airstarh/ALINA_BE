<?php
/** @var $data stdClass */
$vd               = $data;
$dateToUtDayStart = $vd['dateToUtDayStart'];
$dateToUtDayEnd   = $vd['dateToUtDayEnd'];
?>
SELECT
wd.id as wd_id,
assa.firstname as assa_firstname,
assa.lastname as assa_lastname,
wd.for_date as wd_for_date,
wd.amount as wd_amount,
wd.price_final as wd_price_final,
wd.time_spent as wd_time_spent,
assa.mail as assa_mail,

o.name_human as o_name_human,
d.name_human as d_name_human,
p.name_human as p_name_human,
t.name_human as t_name_human,
st.name_human as st_name_human,

w.pm_organization_id as o_id,
w.pm_department_id as d_id,
w.pm_project_id as p_id,
w.pm_task_id as t_id,
w.pm_subtask_id as st_id,
w.id as w_id,

o.name_human as o_nh,
d.name_human as d_nh,
p.name_human as p_nh,
t.name_human as t_nh,
st.name_human as st_nh,
w.name_human as w_nh,

assa.emblem as assa_emblem,
wd.assignee_id as wd_assignee_id

FROM pm_work_done AS wd


LEFT JOIN user AS assa ON assa.id = wd.assignee_id
LEFT JOIN pm_work AS w ON w.id = wd.pm_work_id
LEFT JOIN pm_subtask AS st ON st.id = w.pm_subtask_id
LEFT JOIN pm_task AS t ON t.id = w.pm_task_id
LEFT JOIN pm_project AS p ON p.id = w.pm_project_id
LEFT JOIN pm_department AS d ON d.id = w.pm_department_id
LEFT JOIN pm_organization AS o ON o.id = w.pm_organization_id

WHERE
wd.for_date >= <?= $dateToUtDayStart ?>

AND
wd.for_date <= <?= $dateToUtDayEnd ?>

ORDER BY
o.name_human ASC,
d.name_human ASC,
p.name_human ASC,
t.name_human ASC,
t.order_in_view ASC,
st.name_human ASC,
st.order_in_view ASC,
assa.lastname ASC,
assa.firstname ASC,
wd.for_date DESC
