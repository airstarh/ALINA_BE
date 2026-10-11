<?php
declare(strict_types=1);

// Local development router: never serve config, credentials, docs or other APIs.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/') {
    header('Location: /life-prototype.php');
    return true;
}
if (in_array($path, [
    '/life-prototype.php', '/stuff/php/life-prototype-stream.php',
    '/stuff/js/life-prototype.js', '/stuff/css/app.css',
], true)) return false;
http_response_code(404);
header('Content-Type: text/plain');
echo 'Not found.';
return true;
