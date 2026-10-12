<?php
function database(): PDO
{
    static $connection = null;
    if ($connection !== null) {
        return $connection;
    }
    $path = __DIR__ . '/config.local.php';
    if (!is_file($path)) {
        http_response_code(503);
        exit('Database setup is missing. Follow the README to create config.local.php.');
    }
    $config = require $path;
    try {
        $connection = new PDO(
            'mysql:host=' . $config['host'] . ';port=' . $config['port'] .
            ';dbname=' . $config['database'] . ';charset=utf8mb4',
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
        // Match PHP and MySQL when determining whether a slot is in the future.
        $connection->exec("SET time_zone = '+03:00'");
    } catch (PDOException $error) {
        error_log('Database connection failed: ' . $error->getMessage());
        http_response_code(503);
        exit('Cannot connect to the database. Check the local configuration and MySQL service.');
    }
    return $connection;
}
