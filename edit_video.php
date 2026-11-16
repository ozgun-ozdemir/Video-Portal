<?php
// edit_video.php
require_once __DIR__ . '/config.php';
require_login();

$id = filter_var($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null),
                 FILTER_VALIDATE_INT);
if (!$id) {
    redirect('videos.php');
}

// Deleted videos can't be edited
$stmt = db()->prepare('SELECT id, link, description FROM video WHERE id = ? AND is_deleted = 0');
$stmt->bind_param('i', $id);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$video) {
    $_SESSION['flash'] = ['error', 'Video not found.'];
    redirect('videos.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $video['link'] = trim($_POST['link'] ?? '');
    $video['description'] = trim($_POST['description'] ?? '');
    $errors = validate_video($video['link'], $video['description']);

    if (!$errors) {
        $stmt = db()->prepare('UPDATE video SET link = ?, description = ? WHERE id = ?');
        $stmt->bind_param('ssi', $video['link'], $video['description'], $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = ['success', 'Video successfully updated.'];
        redirect('videos.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Video</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    <h2>Update Video</h2>
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
            <input type="hidden" name="id" value="<?= (int)$video['id'] ?>">
            <div class="form-group">
                <label for="link">YouTube Link</label>
                <input type="url" id="link" name="link" maxlength="255" value="<?= e($video['link']) ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Video Description</label>
                <textarea id="description" name="description" maxlength="1000" required><?= e($video['description']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-block">Save</button>
        </form>
    </div>
    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
