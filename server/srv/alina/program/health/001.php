<?php

namespace alina\program\health;

use alina\mvc\Model\CurrentUser;
use alina\Utils\Request;

require_once __DIR__ . '/../../AppBoot.php';

// alinaHealthRequest();
alinaHealthUser();

function alinaHealthRequest()
{
    AlinaEchoDraft(Request::obj());
}

function alinaHealthUser()
{
    AlinaEchoDraft([
        CurrentUser::obj()->id(),
        CurrentUser::obj()::KEY_USER_ID,
    ]);
}
