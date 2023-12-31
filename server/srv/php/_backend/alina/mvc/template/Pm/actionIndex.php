<?php
/** @var $data stdClass */

$linkOrganizationList = '/';
$linkDepartmentList   = '';
$linkProjectList      = '';
$linkTaskList         = '';
$linkSubTaskList      = '';
$linkWorkList         = '';
$linkWorkDoneList     = '';

?>

<div class="clear">&nbsp;</div>
<h1><?= ___("Project Manager") ?></h1>
<div class="clear">&nbsp;</div>

<hr>

<div class="mt-5 mb-5">

    <h2><?= ___("Common Things") ?></h2>

    <a href="<?= \alina\mvc\Controller\Pm::URL_FILL_REPORT ?>"
       class="btn btn-primary"
       target="_blank"
    ><?= ___("Report Time") ?></a>

</div>


<hr>

<div class="mt-5 mb-5">
    <h2><?= ___("Administrative Tools") ?></h2>

    <a href="/admindbmanager/models/pm_organization"
       class="btn btn-primary"
       target="_blank"
    ><?= ___("Organization") ?></a>

    <a href="/admindbmanager/models/pm_department"
       class="btn btn-primary"
       target="_blank"
    ><?= ___("Department") ?></a>

    <a href="/admindbmanager/models/pm_project"
       class="btn btn-primary"
       target="_blank"
    ><?= ___("Project") ?></a>

    <a href="/admindbmanager/models/pm_task"
       class="btn btn-primary"
       target="_blank"
    ><?= ___("Task") ?></a>

    <a href="/admindbmanager/models/pm_subtask"
       class="btn btn-primary"
       target="_blank"
    ><?= ___("SubTask") ?></a>

    <a href="/admindbmanager/models/pm_work"
       class="btn btn-secondary"
       target="_blank"
    ><?= ___("Work Unit") ?></a>

    <a href="/admindbmanager/models/pm_work_done"
       class="btn btn-secondary"
       target="_blank"
    ><?= ___("Work Unit Done") ?></a>

    <div class="clear">&nbsp;</div>

    <a href="/admindbmanager/models/user"
       class="btn btn-primary"
       target="_blank"
    ><?= ___("Users") ?></a>

    <a href="<?= \alina\mvc\Controller\Pm::URL_EDIT ?>"
       class="btn btn-primary"
       target="_blank"
    ><?= ___("Edit Structure") ?></a>
</div>
<hr>

