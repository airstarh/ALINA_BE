<?php

$host = getenv('MYSQL_HOST') ?: 'mysql';
$db   = 'stage';
$user = 'root';
$pass = getenv('MYSQL_ROOT_PASSWORD') ?: '';
$port = getenv('MYSQL_PORT') ?: 3306;

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    $stmt = $pdo->query('SELECT * FROM `user` LIMIT 1');
    $row  = $stmt->fetch();

    http_response_code(200);
    echo '<pre>' . htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre>';
}
catch (PDOException $e) {
    http_response_code(500);
    error_log('PDO Error: ' . $e->getMessage());
    echo '<pre>Database error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
catch (Throwable $e) {
    http_response_code(500);
    error_log('Unexpected error: ' . $e->getMessage());
    echo '<pre>Unexpected error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
