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

<h1>Управления проектами</h1>

<div class="ck-content">
    <pre>
    <?php
    print_r($data);
    ?>
</pre>
</div>

<hr>

<div class="mt-5 mb-5">
    <h2>Админимстративная часть</h2>

    <a href="/admindbmanager/models/pm_organization"
       class="btn btn-primary"
       target="_blank"
    >Организации</a>

    <a href="/admindbmanager/models/pm_department"
       class="btn btn-primary"
       target="_blank"
    >Отделы</a>

    <a href="/admindbmanager/models/pm_project"
       class="btn btn-primary"
       target="_blank"
    >Проекты</a>

    <a href="/admindbmanager/models/pm_task"
       class="btn btn-primary"
       target="_blank"
    >Задачи</a>

    <a href="/admindbmanager/models/pm_subtask"
       class="btn btn-primary"
       target="_blank"
    >Подзадачи</a>

    <a href="/admindbmanager/models/pm_work"
       class="btn btn-secondary"
       target="_blank"
    >Единица Работы</a>

    <a href="/admindbmanager/models/pm_work_done"
       class="btn btn-secondary"
       target="_blank"
    >Выполнено</a>
</div>

<hr>

