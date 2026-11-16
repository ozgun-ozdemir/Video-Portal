<?php
// config.php

// Can be overridden with environment variables
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'video_portal');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $ex) {
            error_log($ex->getMessage());
            http_response_code(500);
            exit('Could not connect to the database.');
        }
    }
    return $conn;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function require_login(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

function redirect(string $url): void {
    header("Location: $url");
    exit();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Invalid request.');
    }
}

// Handles watch?v=, youtu.be/, /embed/ and /shorts/ links
function extractYouTubeID(string $url): ?string {
    $pattern = '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~';
    return preg_match($pattern, $url, $m) ? $m[1] : null;
}

function validate_video(string $link, string $description): array {
    $errors = [];
    if ($link === '' || !filter_var($link, FILTER_VALIDATE_URL) || extractYouTubeID($link) === null) {
        $errors[] = 'Please enter a valid YouTube link.';
    } elseif (mb_strlen($link) > 255) {
        $errors[] = 'The link can be at most 255 characters.';
    }
    if ($description === '') {
        $errors[] = 'Description cannot be empty.';
    } elseif (mb_strlen($description) > 1000) {
        $errors[] = 'The description can be at most 1000 characters.';
    }
    return $errors;
}
