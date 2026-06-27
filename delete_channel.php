<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_login();
$user = current_user();

$channelId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
$channel   = owned_channel($channelId, (int)$user['id']);
if (!$channel) { flash_set('error', 'Channel not found.'); redirect('dashboard.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Remove any uploaded track files + the album image for this channel.
    $stmt = db()->prepare("SELECT file_path FROM tracks WHERE channel_id = ? AND source_type = 'upload' AND file_path IS NOT NULL");
    $stmt->execute([$channel['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $abs = __DIR__ . '/' . $row['file_path'];
        if (is_file($abs)) @unlink($abs);
    }
    if (!empty($channel['image_path'])) {
        $abs = __DIR__ . '/' . $channel['image_path'];
        if (is_file($abs)) @unlink($abs);
    }

    // Tracks cascade-delete via the foreign key.
    db()->prepare('DELETE FROM channels WHERE id = ? AND user_id = ?')->execute([$channel['id'], $user['id']]);
    flash_set('success', 'Channel “' . $channel['name'] . '” deleted.');
    redirect('dashboard.php');
}

$pageTitle  = 'GrooveVault — Delete Channel?';
$navVariant = 'user';
require_once('inc/header.inc.php');
?>

<div class="view" style="min-height:100vh;">
  <div class="modal-stage">
    <form method="post" action="delete_channel.php" class="gv-modal-card p-4">
      <input type="hidden" name="id" value="<?= (int)$channel['id'] ?>">
      <?= csrf_field() ?>
      <h5 class="modal-title-gv" style="color:var(--hot-pink);">DELETE CHANNEL?</h5>
      <p style="color:var(--text-muted);margin:1rem 0 1.4rem;">This will permanently delete <strong style="color:var(--text-main);"><?= e($channel['name']) ?></strong> and all of its tracks. This cannot be undone.</p>
      <div class="d-flex justify-content-end gap-2">
        <a href="channel_detail.php?id=<?= (int)$channel['id'] ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-gv-primary" style="background:var(--hot-pink);"><i class="bi bi-trash me-1"></i>Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<?php
require_once('inc/footer.inc.php');
?>
