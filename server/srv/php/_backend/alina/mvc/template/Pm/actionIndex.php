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
    >Организации</a>

    <a href="/admindbmanager/models/pm_department"
       class="btn btn-primary"
    >Отделы</a>

    <a href="/admindbmanager/models/pm_project"
       class="btn btn-primary"
    >Проекты</a>

    <a href="/admindbmanager/models/pm_task"
       class="btn btn-primary"
    >Задачи</a>

    <a href="/admindbmanager/models/pm_subtask"
       class="btn btn-primary"
    >Подзадачи</a>

    <a href="/admindbmanager/models/pm_work"
       class="btn btn-primary"
    >Процедура</a>

    <a href="/admindbmanager/models/pm_work_done"
       class="btn btn-primary"
    >Логирование процесса</a>
</div>

<hr>

<a href="" class="btn btn-primary"
>XXX</a>