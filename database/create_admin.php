<?php
// Run from terminal only: php database/create_admin.php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Command line only.');
}
$configPath = __DIR__ . '/../backend/config.local.php';
if (!is_file($configPath)) {
    exit("Create backend/config.local.php first.\n");
}
$config = require $configPath;
echo "Admin username: ";
$username = trim((string) fgets(STDIN));
echo "Admin password (at least 8 characters): ";
$password = trim((string) fgets(STDIN));
if ($username === '' || strlen($username) > 100 || strlen($password) < 8) {
    exit("Invalid username or password.\n");
}
try {
    $db = new PDO(
        'mysql:host=' . $config['host'] . ';port=' . $config['port'] .
        ';dbname=' . $config['database'] . ';charset=utf8mb4',
        $config['username'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $db->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    echo "Admin created.\n";
} catch (PDOException $error) {
    fwrite(STDERR, "Could not create admin. Run database/sprint2.sql first; choose a unique username.\n");
    exit(1);
}
