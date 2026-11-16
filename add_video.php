<?php
// add_video.php
require_once __DIR__ . '/config.php';
require_login();

$errors = [];
$link = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $link = trim($_POST['link'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $errors = validate_video($link, $description);

    if (!$errors) {
        // date_added and is_deleted use column defaults
        $stmt = db()->prepare('INSERT INTO video (link, description, sort_order) SELECT ?, ?, COALESCE(MAX(sort_order), 0) + 1 FROM video');
        $stmt->bind_param('ss', $link, $description);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = ['success', 'Video successfully added.'];
        redirect('videos.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Video</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    <h2>Add Video</h2>
    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>
    <div class="container">
        <div class="cancel-container">
            <a href="videos.php" class="round-btn cancel-button" aria-label="Cancel">X</a>
            <div class="cancel-text">Cancel</div>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="link">YouTube Link</label>
                <input type="url" id="link" name="link" maxlength="255"
                       placeholder="https://www.youtube.com/watch?v=..." value="<?= e($link) ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Video Description</label>
                <textarea id="description" name="description" maxlength="1000" required><?= e($description) ?></textarea>
            </div>
            <button type="submit" class="btn btn-block">Save</button>
        </form>
    </div>
    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
