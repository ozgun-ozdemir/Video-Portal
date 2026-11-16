<?php
// videos.php
require_once __DIR__ . '/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verify_csrf();
    $deleteId = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);
    if ($deleteId) {
        $stmt = db()->prepare('UPDATE video SET is_deleted = 1 WHERE id = ?');
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $_SESSION['flash'] = $stmt->affected_rows > 0
            ? ['success', 'Video successfully deleted.']
            : ['error', 'Video not found.'];
        $stmt->close();
    }
    // Redirect so refreshing doesn't resubmit the form
    redirect('videos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_id'], $_POST['direction'])) {
    verify_csrf();
    $moveId = filter_var($_POST['move_id'], FILTER_VALIDATE_INT);
    $up = $_POST['direction'] === 'up';

    $stmt = db()->prepare('SELECT sort_order FROM video WHERE id = ? AND is_deleted = 0');
    $stmt->bind_param('i', $moveId);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($current) {
        // Find the visible video directly above or below
        $sql = $up
            ? 'SELECT id, sort_order FROM video WHERE is_deleted = 0 AND sort_order > ? ORDER BY sort_order ASC LIMIT 1'
            : 'SELECT id, sort_order FROM video WHERE is_deleted = 0 AND sort_order < ? ORDER BY sort_order DESC LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->bind_param('i', $current['sort_order']);
        $stmt->execute();
        $neighbor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($neighbor) {
            db()->begin_transaction();
            $stmt = db()->prepare('UPDATE video SET sort_order = ? WHERE id = ?');
            $stmt->bind_param('ii', $neighbor['sort_order'], $moveId);
            $stmt->execute();
            $stmt->bind_param('ii', $current['sort_order'], $neighbor['id']);
            $stmt->execute();
            $stmt->close();
            db()->commit();
        }
    }
    redirect('videos.php');
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$result = db()->query('SELECT id, link, description, date_added FROM video WHERE is_deleted = 0 ORDER BY sort_order DESC, id DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
    <?php endif; ?>

    <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-empty">No videos yet.</div>
    <?php else: ?>
    <table class="video-table">
        <?php $i = 0; $total = $result->num_rows;
        while ($row = $result->fetch_assoc()):
            $i++;
            $ytId = extractYouTubeID($row['link']); ?>
            <tr>
                <td class="thumb">
                    <a href="<?= e($row['link']) ?>" target="_blank" rel="noopener noreferrer">
                        <?php if ($ytId): ?>
                            <img class="video-thumbnail" loading="lazy"
                                 src="https://img.youtube.com/vi/<?= e($ytId) ?>/mqdefault.jpg"
                                 alt="<?= e($row['description']) ?>">
                        <?php else: ?>
                            Open video
                        <?php endif; ?>
                    </a>
                </td>
                <td class="desc">
                    <p><?= nl2br(e($row['description'])) ?></p>
                    <p class="date">Date Added: <?= e(date('d.m.Y', strtotime($row['date_added']))) ?></p>
                </td>
                <td class="actions">
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="move_id" value="<?= (int)$row['id'] ?>">
                        <button type="submit" name="direction" value="up" class="btn btn-move" aria-label="Move up" <?= $i === 1 ? 'disabled' : '' ?>>▲</button>
                        <button type="submit" name="direction" value="down" class="btn btn-move" aria-label="Move down" <?= $i === $total ? 'disabled' : '' ?>>▼</button>
                    </form>
                    <a class="btn" href="edit_video.php?id=<?= (int)$row['id'] ?>">Update</a>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to remove this video?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                        <button type="submit" class="btn btn-delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <?php endif; ?>
    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
