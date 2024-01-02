<?php
/** @var $data stdClass */
$vd               = $data;
$dateToUtDayStart = $vd['dateToUtDayStart'];
$dateToUtDayEnd   = $vd['dateToUtDayEnd'];
?>
SELECT
wd.id as wd_id,
u.firstname as u_firstname,
u.lastname as u_lastname,
wd.price_final as wd_price_final,
wd.time_spent as wd_time_spent,
u.mail as u_mail,

o.name_human as o_name_human,
d.name_human as d_name_human,
p.name_human as p_name_human,
t.name_human as t_name_human,
st.name_human as st_name_human,

u.emblem as u_emblem,
wd.assignee_id as wd_assignee_id


FROM pm_work_done AS wd


LEFT JOIN user AS u ON u.id = wd.assignee_id
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
st.name_human ASC,
wd.for_date DESC,
u.lastname ASC,
u.firstname ASC