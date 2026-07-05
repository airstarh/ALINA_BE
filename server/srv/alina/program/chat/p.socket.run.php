<?php

namespace alina\program\chat;

use alina\Services\Chat\ChatServerRunner;

require_once __DIR__ . '/../../AppBoot.php';

ChatServerRunner::go();
