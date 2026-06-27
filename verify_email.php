<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_once __DIR__ . '/inc/mail.inc.php';

// ── 1. Token link clicked from email ──────────────────────────────────────────
if (isset($_GET['token']) && $_GET['token'] !== '') {
    $token = trim($_GET['token']);
    $stmt  = db()->prepare('SELECT * FROM users WHERE email_verification_token = ? AND email_verified = 0');
    $stmt->execute([$token]);
    $user  = $stmt->fetch();

    if (!$user) {
        // Token invalid or already used.
        flash_set('error', 'This verification link is invalid or has already been used. Please log in or register again.');
        redirect('login.php');
    }

    // Mark verified, clear token, log the user in.
    db()->prepare('UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE id = ?')
        ->execute([$user['id']]);
    $_SESSION['user_id'] = (int)$user['id'];
    unset($_SESSION['pending_verification_user']);
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
    flash_set('success', 'Email verified! Pick a plan to get started.');
    redirect('pick_plan.php');
}

// ── 2. Resend request (POST) ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $pendingId = $_SESSION['pending_verification_user'] ?? null;
    if (!$pendingId) {
        redirect('register.php');
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND email_verified = 0');
    $stmt->execute([$pendingId]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['pending_verification_user']);
        redirect('register.php');
    }

    // Generate a fresh token.
    $newToken = random_token(16);
    db()->prepare('UPDATE users SET email_verification_token = ? WHERE id = ?')
        ->execute([$newToken, $user['id']]);

    $scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir        = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $verifyUrl  = $scheme . '://' . $host . $dir . '/verify_email.php?token=' . urlencode($newToken);

    if (mail_configured()) {
        $html = '
<!DOCTYPE html>
<html>
<body style="background:#0d0d12;margin:0;padding:30px;font-family:Arial,sans-serif;">
  <div style="max-width:480px;margin:auto;background:#18181f;border-radius:16px;padding:32px;text-align:center;">
    <div style="font-size:3rem;margin-bottom:16px;">🎵</div>
    <h2 style="color:#ffffff;margin:0 0 8px;">Verify Your Email</h2>
    <p style="color:#a0a0b0;font-size:0.95rem;margin:0 0 24px;">
      Hi <strong style="color:#ffffff;">' . htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8') . '</strong>!<br>
      Click the button below to verify your email address.
    </p>
    <a href="' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '"
       style="display:inline-block;background:linear-gradient(135deg,#FF2D78,#FF6B00);
              color:#fff;text-decoration:none;padding:14px 36px;border-radius:50px;
              font-weight:700;font-size:1rem;letter-spacing:.5px;">
      Verify My Email
    </a>
    <p style="color:#606070;font-size:0.78rem;margin-top:24px;">
      If you did not create this account, you can safely ignore this email.
    </p>
  </div>
</body>
</html>';
        send_email($user['email'], $user['display_name'], 'Verify your GrooveVault account', $html);
        flash_set('info', 'Verification email resent! Check your inbox.');
    } else {
        flash_set('error', 'Email service is not configured. Please contact support.');
    }
    redirect('verify_email.php');
}

// ── 3. Show "check your email" page ───────────────────────────────────────────
$pendingId = $_SESSION['pending_verification_user'] ?? null;
if (!$pendingId) {
    redirect('register.php');
}

$stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND email_verified = 0');
$stmt->execute([$pendingId]);
$user = $stmt->fetch();
if (!$user) {
    unset($_SESSION['pending_verification_user']);
    redirect('register.php');
}

$pageTitle  = 'GrooveVault — Verify Email';
$navVariant = 'guest';
require_once('inc/header.inc.php');
?>

<div class="view" style="min-height:100vh;">
  <div class="modal-stage">
    <div class="gv-modal-card p-4 text-center">

      <div style="font-size:3rem;margin-bottom:1rem;">📧</div>
      <h5 style="color:var(--neon-green);font-weight:700;">Check Your Email!</h5>

      <?= flash_render() ?>

      <p style="color:var(--text-muted);font-size:.9rem;margin-top:.5rem;">
        We sent a verification link to<br>
        <strong style="color:var(--text-main);"><?= e($user['email']) ?></strong>
      </p>

      <p style="color:var(--text-muted);font-size:.82rem;margin-top:.75rem;margin-bottom:1.5rem;">
        Open that email and click <strong style="color:var(--text-main);">Verify My Email</strong>
        to activate your account. Check your spam folder if you don't see it.
      </p>

      <?php if (mail_configured()): ?>
        <form method="post" action="verify_email.php">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline-secondary w-100" style="border-color:var(--border-color);color:var(--text-muted);font-size:.85rem;">
            <i class="bi bi-arrow-clockwise me-1"></i> Resend Verification Email
          </button>
        </form>
      <?php endif; ?>

      <p style="font-size:.78rem;color:var(--text-muted);margin-top:1.25rem;margin-bottom:0;">
        Wrong email? <a href="register.php" class="text-pink text-decoration-none">Register again</a>
      </p>
    </div>
  </div>
</div>

<?php
require_once('inc/footer.inc.php');
?>
