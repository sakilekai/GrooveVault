<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_once __DIR__ . '/inc/mail.inc.php';

// Already logged in? Go to the dashboard.
if (current_user()) redirect('dashboard.php');

$errors = [];
$old = ['display_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (setting('allow_signups', '1') !== '1') {
        $errors[] = 'New sign-ups are currently disabled. Please check back later.';
    }

    $name    = trim($_POST['display_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $old['display_name'] = $name;
    $old['email'] = $email;

    if ($name === '')                                   $errors[] = 'Display name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Please enter a valid email address.';
    if (strlen($pass) < 8)                              $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $confirm)                             $errors[] = 'Passwords do not match.';

    if (!$errors) {
        // Unique email?
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        $token = random_token(16);
        $stmt = db()->prepare(
            'INSERT INTO users (display_name, email, password_hash, email_verified, email_verification_token)
             VALUES (?, ?, ?, 0, ?)'
        );
        $stmt->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT), $token]);
        $userId = (int)db()->lastInsertId();

        // Build the absolute verification URL so it works in any email client.
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $verifyUrl = $scheme . '://' . $host . $dir . '/verify_email.php?token=' . urlencode($token);

        // Send verification email (if mail is configured).
        if (mail_configured()) {
            $html = '
<!DOCTYPE html>
<html>
<body style="background:#0d0d12;margin:0;padding:30px;font-family:Arial,sans-serif;">
  <div style="max-width:480px;margin:auto;background:#18181f;border-radius:16px;padding:32px;text-align:center;">
    <div style="font-size:3rem;margin-bottom:16px;">🎵</div>
    <h2 style="color:#ffffff;margin:0 0 8px;">Verify Your Email</h2>
    <p style="color:#a0a0b0;font-size:0.95rem;margin:0 0 24px;">
      Hi <strong style="color:#ffffff;">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong>, welcome to GrooveVault!<br>
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
            send_email($email, $name, 'Verify your GrooveVault account', $html);
        }

        $_SESSION['pending_verification_user'] = $userId;
        flash_set('info', 'Account created! Check your email for a verification link.');
        redirect('verify_email.php');
    }
}

$pageTitle  = 'GrooveVault — Register';
$navVariant = 'guest';
require_once('inc/header.inc.php');
?>

<div class="view" style="min-height:100vh;">
  <div class="modal-stage">
    <form method="post" action="register.php" class="gv-modal-card p-4">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <h5 class="modal-title-gv">CREATE YOUR <span class="text-pink">ACCOUNT</span></h5>
        <a href="index.php" class="bi bi-x-lg text-decoration-none" style="color:var(--text-muted);"></a>
      </div>
      <?php foreach ($errors as $err): ?>
        <div class="gv-alert gv-alert-danger mb-2" style="font-size:.82rem;"><?= e($err) ?></div>
      <?php endforeach; ?>
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Display Name</label><input name="display_name" class="form-control" placeholder="DJ AwesomeSauce" value="<?= e($old['display_name']) ?>" required></div>
      <div class="mb-3"><label class="form-label">Email Address</label><input name="email" type="email" class="form-control" placeholder="you@example.com" value="<?= e($old['email']) ?>" required></div>
      <div class="mb-3"><label class="form-label">Password</label><input name="password" class="form-control" type="password" placeholder="Min. 8 characters" required></div>
      <div class="mb-3"><label class="form-label">Confirm Password</label><input name="confirm_password" class="form-control" type="password" placeholder="Repeat password" required></div>
      <button type="submit" class="btn btn-gv-primary w-100">Continue <i class="bi bi-arrow-right ms-1"></i></button>
      <p class="text-center mt-3 mb-0" style="font-size:.85rem;color:var(--text-muted);">Already have an account? <a href="login.php" class="text-blue text-decoration-none">Log in</a></p>
    </form>
  </div>
</div>

<?php
require_once('inc/footer.inc.php');
?>
