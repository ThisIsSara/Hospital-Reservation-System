<?php
// Read local settings and connect to MySQL using PDO.
function database(): PDO
{
    static $connection = null;
    if ($connection !== null) {
        return $connection;
    }
    $path = __DIR__ . '/config.local.php';
    if (!is_file($path)) {
        respond(['success' => false, 'message' => 'Database configuration is missing. Follow the README.'], 503);
    }
    $config = require $path;
    try {
        $connection = new PDO(
            'mysql:host=' . $config['host'] . ';port=' . $config['port'] .
            ';dbname=' . $config['database'] . ';charset=utf8mb4',
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
        $connection->exec("SET time_zone = '+03:00'");
    } catch (PDOException $error) {
        error_log('Database connection failed: ' . $error->getMessage());
        respond(['success' => false, 'message' => 'Cannot connect to MySQL. Check the local configuration and service.'], 503);
    }
    return $connection;
}
