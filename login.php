<?php
// login.php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('videos.php');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, username, password FROM admin WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['username'] = $admin['username'];
        redirect('videos.php');
    }
    $message = 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header"><h1>Video Admin</h1></div>
    <div class="container login">
        <h2>Login</h2>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" autocomplete="username"
                       value="<?= e($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-block">Login</button>
            <?php if ($message): ?>
                <div class="alert alert-error"><?= e($message) ?></div>
            <?php endif; ?>
        </form>
    </div>
    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
