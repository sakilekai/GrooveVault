<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_once __DIR__ . '/inc/mail.inc.php';

/* Three-step reset:
   step 1  request    — enter email, create a reset token + email it via Brevo
   step 2  link sent  — confirmation + optional resend
   step 3  new password — set a new password, return to login            */

$step      = $_GET['step'] ?? '1';
$resetLink = null;
$emailSent = false;
$mailError = null;
$errors    = [];
$token     = $_GET['token'] ?? ($_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'request' || $action === 'resend') {
        $email = trim($_POST['email'] ?? ($_SESSION['pw_reset_email'] ?? ''));
        if ($email === '') {
            flash_set('error', 'Please enter your account email.');
            redirect('forgot_password.php');
        }

        $stmt = db()->prepare('SELECT id, display_name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u) {
            $tok = random_token(20);
            db()->prepare(
                'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
            )->execute([$u['id'], $tok]);

            $resetLink = 'forgot_password.php?step=3&token=' . urlencode($tok);
            $absLink   = mail_absolute_url($resetLink);
            $_SESSION['pw_reset_email'] = $email;

            if (mail_configured()) {
                $res       = send_password_reset_email($email, $u['display_name'], $absLink);
                $emailSent = $res['ok'];
                if (!$res['ok']) {
                    $mailError = $res['error'];
                }
            }
        } else {
            unset($_SESSION['pw_reset_email']);
        }

        if ($action === 'resend') {
            if ($emailSent) {
                flash_set('info', 'Reset link sent again. Check your inbox and spam folder.');
            } elseif ($mailError) {
                flash_set('error', 'Could not resend email: ' . $mailError);
            }
        }

        $step = '2';
    }

    if ($action === 'reset') {
        $pass    = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($pass) < 8)   $errors[] = 'Password must be at least 8 characters.';
        if ($pass !== $confirm)  $errors[] = 'Passwords do not match.';

        $row = false;
        if (!$errors) {
            $stmt = db()->prepare(
                'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()'
            );
            $stmt->execute([$token]);
            $row = $stmt->fetch();
            if (!$row) $errors[] = 'This reset link is invalid or has expired.';
        }
        if (!$errors && $row) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($pass, PASSWORD_BCRYPT), $row['user_id']]);
            db()->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$row['id']]);
            unset($_SESSION['pw_reset_email']);
            flash_set('success', 'Password updated. Please log in with your new password.');
            redirect('login.php');
        }
        $step = '3';
    }
}

$pageTitle  = 'GrooveVault — Forgot / Reset Password';
$navVariant = 'guest';
require_once('inc/header.inc.php');
?>

<div class="view" style="min-height:100vh;">
  <div class="modal-stage">
    <div class="d-flex gap-3 flex-wrap justify-content-center" style="position:relative;z-index:2;">

      <?php if ($step === '1'): ?>
      <form method="post" action="forgot_password.php" class="gv-modal-card p-4" style="max-width:340px;">
        <input type="hidden" name="action" value="request">
        <?= csrf_field() ?>
        <span class="badge-gv mb-2 d-inline-block">STEP 1 · REQUEST</span>
        <h6 class="modal-title-gv" style="font-size:1.2rem;">RESET YOUR <span class="text-pink">PASSWORD</span></h6>
        <?= flash_render() ?>
        <p style="font-size:.82rem;color:var(--text-muted);">Enter your account email and we will send a reset link via email.</p>
        <div class="mb-3"><label class="form-label">Account Email</label><input name="email" type="email" class="form-control" placeholder="you@example.com" required></div>
        <button type="submit" class="btn btn-gv-primary w-100">Send Reset Link</button>
      </form>

      <?php elseif ($step === '2'): ?>
      <div class="gv-modal-card p-4 text-center" style="max-width:340px;">
        <span class="badge-gv mb-2 d-inline-block">STEP 2 · SENT</span>
        <div style="font-size:2.4rem;margin:.4rem 0;">📨</div>
        <?= flash_render() ?>
        <p style="font-size:.85rem;color:var(--text-muted);">If an account exists for that email, a reset link has been sent from <strong style="color:var(--text-main);"><?= e(MAIL_FROM_NAME) ?></strong>. Check your inbox and spam folder.</p>

        <?php if (mail_configured()): ?>
          <?php if ($mailError): ?>
            <div class="gv-alert gv-alert-danger" style="font-size:.78rem;">We could not send the email: <?= e($mailError) ?></div>
          <?php elseif ($emailSent): ?>
            <div class="gv-alert gv-alert-success" style="font-size:.78rem;">Reset email sent successfully via Brevo.</div>
          <?php endif; ?>

          <?php if (!empty($_SESSION['pw_reset_email'])): ?>
            <form method="post" action="forgot_password.php?step=2" class="mt-2">
              <input type="hidden" name="action" value="resend">
              <input type="hidden" name="email" value="<?= e($_SESSION['pw_reset_email']) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-outline-secondary w-100" style="border-color:var(--border-color);color:var(--text-muted);font-size:.85rem;">
                <i class="bi bi-arrow-clockwise me-1"></i> Resend Reset Email
              </button>
            </form>
          <?php endif; ?>

          <a href="login.php" class="btn btn-gv-outline w-100 mt-2">Back to Login</a>

        <?php elseif ($resetLink): ?>
          <div class="gv-alert gv-alert-info" style="font-size:.78rem;">Brevo is not configured yet, so here is your reset link directly (dev mode only).</div>
          <a href="<?= e($resetLink) ?>" class="btn btn-gv-outline w-100 mt-2">Open Reset Link <i class="bi bi-arrow-right ms-1"></i></a>
        <?php else: ?>
          <a href="login.php" class="btn btn-gv-outline w-100 mt-2">Back to Login</a>
        <?php endif; ?>
      </div>

      <?php else: /* step 3 */ ?>
      <form method="post" action="forgot_password.php" class="gv-modal-card p-4" style="max-width:340px;">
        <input type="hidden" name="action" value="reset">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <?= csrf_field() ?>
        <span class="badge-gv mb-2 d-inline-block">STEP 3 · NEW PASSWORD</span>
        <h6 class="modal-title-gv" style="font-size:1.2rem;">SET A NEW <span class="text-blue">PASSWORD</span></h6>
        <?php foreach ($errors as $err): ?>
          <div class="gv-alert gv-alert-danger mb-2" style="font-size:.8rem;"><?= e($err) ?></div>
        <?php endforeach; ?>
        <div class="mb-3"><label class="form-label">New Password</label><input name="password" class="form-control" type="password" placeholder="Min. 8 characters" required></div>
        <div class="mb-3"><label class="form-label">Confirm Password</label><input name="confirm_password" class="form-control" type="password" placeholder="Repeat password" required></div>
        <button type="submit" class="btn btn-gv-primary w-100">Save &amp; Log In</button>
      </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php
require_once('inc/footer.inc.php');
?>
