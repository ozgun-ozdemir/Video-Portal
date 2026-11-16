<?php
// create_admin.php
// Usage: php create_admin.php <username> <password>
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}
require_once __DIR__ . '/config.php';

if ($argc < 3) {
    exit("Usage: php create_admin.php <username> <password>\n");
}
[$_, $username, $password] = $argv;
if (strlen($password) < 8) {
    exit("Password must be at least 8 characters.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare('INSERT INTO admin (username, password) VALUES (?, ?)');
$stmt->bind_param('ss', $username, $hash);
try {
    $stmt->execute();
    echo "Admin '$username' created.\n";
} catch (mysqli_sql_exception $ex) {
    echo "Error: this username may already exist.\n";
}
