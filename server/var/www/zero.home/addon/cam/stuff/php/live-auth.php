<?php

declare(strict_types=1);

use Cam\Config;
use Cam\LiveAuthorization;
use Cam\TalkToken;

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/LiveStream.php';
require_once __DIR__ . '/TalkToken.php';
require_once __DIR__ . '/LiveAuthorization.php';

try {
    $authorization = new LiveAuthorization(
        Config::load(dirname(__DIR__, 2) . '/config.php'),
        TalkToken::fromEnvironment()
    );
    $authorization->authorize(LiveAuthorization::requestQuery($_SERVER, $_GET), time());
    http_response_code(204);
} catch (Throwable) {
    http_response_code(401);
}

header('Cache-Control: no-store, max-age=0');
header('Content-Length: 0');
