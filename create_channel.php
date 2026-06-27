<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_login();
$user = current_user();
require_active_plan();

$editId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$channel = null;
$errors  = [];

// Editing? Load the channel and confirm ownership.
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM channels WHERE id = ? AND user_id = ?');
    $stmt->execute([$editId, $user['id']]);
    $channel = $stmt->fetch();
    if (!$channel) { flash_set('error', 'Channel not found.'); redirect('dashboard.php'); }
}

$gradients = gv_gradients();
$emojis    = ['🎵','🎸','🎹','🥁','🎤','🔥','⚡','🌙'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $emoji = $_POST['emoji'] ?? '';
    $color = $_POST['bg_color'] ?? $gradients[0];

    if ($name === '')                       $errors[] = 'Channel name is required.';
    if ($emoji !== '' && !in_array($emoji, $emojis, true)) $emoji = '';
    if (!in_array($color, $gradients, true)) $color = $gradients[0];

    // Enforce the channel limit on creation (not on edit).
    if (!$editId && user_channel_count((int)$user['id']) >= user_channel_limit($user)) {
        $errors[] = 'You have reached your plan\'s channel limit. Upgrade for more.';
    }

    // Optional album image: keep current, upload a new one, or remove it.
    $oldImage  = $channel['image_path'] ?? null;
    $imagePath = $oldImage;
    if (!$errors && !empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (!isset($allowed[$mime])) {
            $errors[] = 'Album image must be a JPG, PNG, WEBP or GIF.';
        } elseif ($_FILES['image']['size'] > 4 * 1024 * 1024) {
            $errors[] = 'Album image must be under 4 MB.';
        } else {
            $dir = __DIR__ . '/uploads/channels';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $fname = 'ch_' . $user['id'] . '_' . random_token(6) . '.' . $allowed[$mime];
            move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $fname);
            $imagePath = 'uploads/channels/' . $fname;
        }
    } elseif (!$errors && !empty($_POST['remove_image'])) {
        // "Remove image" checked and no replacement uploaded.
        $imagePath = null;
    }

    if (!$errors) {
        if ($editId) {
            db()->prepare('UPDATE channels SET name = ?, emoji_icon = ?, bg_color = ?, image_path = ? WHERE id = ? AND user_id = ?')
                ->execute([$name, $emoji ?: null, $color, $imagePath, $editId, $user['id']]);
            // Clean up the old file when it was replaced or removed.
            if ($oldImage && $oldImage !== $imagePath) {
                $abs = __DIR__ . '/' . $oldImage;
                if (is_file($abs)) @unlink($abs);
            }
            flash_set('success', 'Channel updated.');
            redirect('channel_detail.php?id=' . $editId);
        } else {
            $token = random_token(4);
            db()->prepare(
                'INSERT INTO channels (user_id, name, emoji_icon, bg_color, image_path, public_token)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$user['id'], $name, $emoji ?: null, $color, $imagePath, $token]);
            $newId = (int)db()->lastInsertId();
            flash_set('success', 'Channel created! Add your first track.');
            redirect('channel_detail.php?id=' . $newId);
        }
    }
}

// Current values for the form (POST repopulation > existing record > defaults).
$curName  = $_POST['name']     ?? $channel['name']       ?? '';
$curEmoji = $_POST['emoji']    ?? $channel['emoji_icon'] ?? '🎵';
$curColor = $_POST['bg_color'] ?? $channel['bg_color']   ?? $gradients[0];

$pageTitle  = 'GrooveVault — ' . ($editId ? 'Edit' : 'Create') . ' Channel';
$navVariant = 'user';
require_once('inc/header.inc.php');
?>

<div class="view" style="min-height:100vh;">
  <div class="modal-stage">
    <form method="post" enctype="multipart/form-data" action="create_channel.php<?= $editId ? '?id=' . $editId : '' ?>" class="gv-modal-card lg p-4">
      <h5 class="modal-title-gv mb-3"><?= $editId ? 'EDIT' : 'CREATE NEW' ?> <span class="text-pink">CHANNEL</span></h5>
      <?php foreach ($errors as $err): ?>
        <div class="gv-alert gv-alert-danger mb-2" style="font-size:.82rem;"><?= e($err) ?></div>
      <?php endforeach; ?>
      <?= csrf_field() ?>
      <input type="hidden" name="emoji"    id="emojiField"  value="<?= e($curEmoji) ?>">
      <input type="hidden" name="bg_color" id="colorField"  value="<?= e($curColor) ?>">
      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label">Channel / Album Name</label>
          <input name="name" class="form-control mb-3" placeholder="e.g. Late Night Chill" value="<?= e($curName) ?>" required>
          <label class="form-label">Album Image / Thumbnail <span class="badge-gv-pink badge-gv" style="font-size:.6rem;">NEW</span></label>
          <?php $hasImage = !empty($channel['image_path']); ?>
          <?php if ($hasImage): ?>
            <div id="currentImageBox" class="mb-2" style="position:relative;display:inline-block;">
              <img src="<?= e($channel['image_path']) ?>?v=<?= e($channel['updated_at'] ?? '') ?>" alt="Current album art" style="width:120px;height:120px;object-fit:cover;border-radius:14px;border:1.5px solid var(--card-border);display:block;">
              <button type="button" id="removeImageBtn" title="Remove image" style="position:absolute;top:-8px;right:-8px;width:26px;height:26px;border-radius:50%;border:none;background:var(--hot-pink);color:#fff;cursor:pointer;line-height:1;"><i class="bi bi-x-lg" style="font-size:.7rem;"></i></button>
            </div>
            <div id="imageRemovedNote" class="mb-2" style="display:none;font-size:.78rem;color:var(--hot-pink);"><i class="bi bi-trash me-1"></i>Image will be removed when you save. <a href="#" id="undoRemoveImage" class="text-blue text-decoration-none">Undo</a></div>
            <input type="hidden" name="remove_image" id="removeImageField" value="0">
          <?php endif; ?>
          <label class="upload-zone d-block" style="cursor:pointer;">
            <i class="bi bi-image" style="font-size:2rem;color:var(--electric-blue);"></i>
            <p style="color:var(--text-muted);margin:.6rem 0 0;font-size:.85rem;">Click to <span class="text-blue">browse</span> for <?= $hasImage ? 'a new image' : 'your album art' ?></p>
            <p style="font-size:.74rem;color:var(--text-muted);margin:0;" id="fileName">PNG / JPG · square works best</p>
            <input type="file" name="image" id="imageInput" accept="image/*" hidden onchange="document.getElementById('fileName').textContent = this.files[0] ? this.files[0].name : 'PNG / JPG · square works best';">
          </label>
        </div>
        <div class="col-md-6">
          <label class="form-label">Emoji Icon <small style="text-transform:none;">(fallback if no image)</small></label>
          <div class="d-flex gap-2 flex-wrap mb-3" id="emojiPicker">
            <?php foreach ($emojis as $em): ?>
              <span class="emoji-pick" data-emoji="<?= e($em) ?>" style="font-size:1.4rem;cursor:pointer;padding:.2rem .4rem;border-radius:8px;border:2px solid <?= $em === $curEmoji ? '#fff' : 'transparent' ?>;opacity:<?= $em === $curEmoji ? '1' : '.5' ?>;"><?= e($em) ?></span>
            <?php endforeach; ?>
          </div>
          <label class="form-label">Background Color</label>
          <div class="d-flex gap-2 flex-wrap" id="colorPicker">
            <?php foreach ($gradients as $g): ?>
              <span class="color-swatch<?= $g === $curColor ? ' selected' : '' ?>" data-color="<?= e($g) ?>" style="background:<?= e($g) ?>;cursor:pointer;"></span>
            <?php endforeach; ?>
          </div>
          <p style="font-size:.78rem;color:var(--text-muted);margin-top:.8rem;">Image, emoji &amp; color let every channel look unique.</p>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-4 gv-form-actions">
        <a href="<?= $editId ? 'channel_detail.php?id=' . $editId : 'dashboard.php' ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-gv-primary"><i class="bi bi-check2 me-1"></i>Save Channel</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.querySelectorAll('#emojiPicker .emoji-pick').forEach(function (el) {
    el.addEventListener('click', function () {
      document.querySelectorAll('#emojiPicker .emoji-pick').forEach(function (s) {
        s.style.opacity = '.5';
        s.style.border  = '2px solid transparent';
      });
      el.style.opacity = '1';
      el.style.border  = '2px solid #fff';
      document.getElementById('emojiField').value = el.dataset.emoji;
    });
  });
  document.querySelectorAll('#colorPicker .color-swatch').forEach(function (el) {
    el.addEventListener('click', function () {
      document.querySelectorAll('#colorPicker .color-swatch').forEach(s => s.classList.remove('selected'));
      el.classList.add('selected');
      document.getElementById('colorField').value = el.dataset.color;
    });
  });

  // Current album image: remove / undo / replace.
  (function () {
    var removeBtn = document.getElementById('removeImageBtn');
    if (!removeBtn) return;                       // no existing image
    var box   = document.getElementById('currentImageBox');
    var note  = document.getElementById('imageRemovedNote');
    var field = document.getElementById('removeImageField');
    var undo  = document.getElementById('undoRemoveImage');
    var fileInput = document.getElementById('imageInput');

    function markRemoved(yes) {
      field.value = yes ? '1' : '0';
      box.style.display  = yes ? 'none' : 'inline-block';
      note.style.display = yes ? 'block' : 'none';
    }
    removeBtn.addEventListener('click', function () { markRemoved(true); });
    if (undo) undo.addEventListener('click', function (e) { e.preventDefault(); markRemoved(false); });
    // Picking a new file cancels a pending removal (the upload replaces it).
    if (fileInput) fileInput.addEventListener('change', function () { if (this.files[0]) markRemoved(false); });
  })();
</script>

<?php
require_once('inc/footer.inc.php');
?>
