<?php

namespace alina\program\chat;

use alina\Services\Chat\ChatServerRunner;

error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../../AppBoot.php';

ChatServerRunner::go();
