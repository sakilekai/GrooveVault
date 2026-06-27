<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_login();
$user = current_user();

$channelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$channel   = owned_channel($channelId, (int)$user['id']);
if (!$channel) { flash_set('error', 'Channel not found.'); redirect('dashboard.php'); }

// Build the absolute public URL from the current request.
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir     = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$shareUrl = $scheme . '://' . $host . $dir . '/public_channel.php?c=' . urlencode($channel['public_token']);

$pageTitle  = 'GrooveVault — Share Channel';
$navVariant = 'user';
require_once('inc/header.inc.php');
?>

<div class="view" style="min-height:100vh;">
  <div class="modal-stage">
    <div class="gv-modal-card p-4">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <h5 class="modal-title-gv mb-0">SHARE <span class="text-blue">CHANNEL</span></h5>
        <a href="channel_detail.php?id=<?= (int)$channel['id'] ?>" class="bi bi-x-lg text-decoration-none" style="color:var(--text-muted);"></a>
      </div>
      <p style="color:var(--text-muted);font-size:.88rem;" class="mb-2">Share this link with anyone — they can listen without an account!</p>
      <div class="share-url mb-3" id="shareUrl"><?= e($shareUrl) ?></div>
      <button class="btn btn-gv-primary w-100" id="copyBtn"><i class="bi bi-clipboard me-2"></i>Copy Link</button>
      <div class="d-flex gap-2 mt-3">
        <a href="https://twitter.com/intent/tweet?text=<?= rawurlencode('Listen to my channel: ' . $channel['name']) ?>&url=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary flex-fill btn-sm"><i class="bi bi-twitter-x me-1"></i>X</a>
        <a href="https://wa.me/?text=<?= rawurlencode($channel['name'] . ' — ' . $shareUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary flex-fill btn-sm"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
        <a href="mailto:?subject=<?= rawurlencode('Check out my GrooveVault channel') ?>&body=<?= rawurlencode($channel['name'] . ': ' . $shareUrl) ?>" class="btn btn-outline-secondary flex-fill btn-sm"><i class="bi bi-envelope me-1"></i>Email</a>
      </div>
      <p class="text-center mt-3 mb-0"><a href="public_channel.php?c=<?= urlencode($channel['public_token']) ?>" target="_blank" rel="noopener" class="text-blue text-decoration-none" style="font-size:.85rem;">Preview public view <i class="bi bi-arrow-right ms-1"></i></a></p>
    </div>
  </div>
</div>

<script>
  document.getElementById('copyBtn').addEventListener('click', function () {
    var url = document.getElementById('shareUrl').textContent.trim();
    var btn = this;
    function done() { btn.innerHTML = '<i class="bi bi-check2 me-2"></i>Copied!'; setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard me-2"></i>Copy Link'; }, 1800); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(done).catch(fallback);
    } else { fallback(); }
    function fallback() {
      var ta = document.createElement('textarea'); ta.value = url; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); done(); } catch (e) {}
      document.body.removeChild(ta);
    }
  });
</script>

<?php
require_once('inc/footer.inc.php');
?>
