<?php

use alina\mvc\model\CurrentUser;
use alina\utils\Html;

$cu = alina\mvc\model\CurrentUser::obj();
$ua = $cu->attributes();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="/"><?= AlinaCFG('title'); ?></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    Tools
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="/tools/SerializedDataEditor">Serialized Data Editor</a>
                </div>
            </li>

            <?php if (AlinaAccessIfAdmin()) { ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                        Admin Tools
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="/CtrlDataTransformations/json">JSON search-replace</a>
                        <a class="dropdown-item" href="/AdminDbManager/DbTablesColumnsInfo">MySQL Manager</a>
                        <a class="dropdown-item" href="/SendRestApiQueries/BaseCurlCalls">HTTP calls with cURL</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Something else here</a>
                    </div>
                </li>
            <?php } ?>

            <?php if (AlinaAccessIfAdmin()) { ?>
            <?php } ?>

            <?php if (AlinaAccessIfAdmin()) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="#">Link</a>
                </li>
            <?php } ?>

            <?php if (AlinaAccessIfAdmin()) { ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                        Dropdown
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="#">Action</a>
                        <a class="dropdown-item" href="#">Another action</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Something else here</a>
                    </div>
                </li>
            <?php } ?>

            <?php if (AlinaAccessIfAdmin()) { ?>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
                </li>
            <?php } ?>
        </ul>
        <ul class="navbar-nav my-2 my-lg-0">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    <?= $cu->name() ?>
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <?php if (!$cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/Auth/Register/') ?>">Register</a> <?php endif; ?>
                    <?php if (!$cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/Auth/Login/') ?>">LogIn</a><?php endif; ?>
                    <?php if ($cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/Auth/Profile') ?>">Profile</a><?php endif; ?>
                    <?php if ($cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/Auth/ChangePassword') ?>">Change Password</a><?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <?php if ($cu->isLoggedIn()): ?><a class="dropdown-item" href="<?= Html::aRef('/Auth/LogOut/') ?>">Exit</a><?php endif; ?>
                </div>
            </li>
        </ul>

        <?php if (AlinaAccessIfAdmin()) { ?>
            <form class="form-inline my-2 my-lg-0">
                <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
            </form>
        <?php } ?>
    </div>
</nav>
