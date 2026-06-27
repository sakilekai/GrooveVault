<?php
require_once __DIR__ . '/inc/functions.inc.php';

if (current_user()) redirect('dashboard.php');

$errors = [];
$old = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $old['email'] = $email;

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        $errors[] = 'Incorrect email or password.';
    } elseif ($user['status'] === 'suspended') {
        $errors[] = 'This account has been suspended. Contact support.';
    } elseif (!$user['email_verified']) {
        // Email not verified yet — send them to the verification page.
        $_SESSION['pending_verification_user'] = (int)$user['id'];
        redirect('verify_email.php');
    } else {
        $_SESSION['user_id'] = (int)$user['id'];
        db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
        // No plan yet -> pick a plan; otherwise straight to the dashboard.
        redirect($user['plan_id'] ? 'dashboard.php' : 'pick_plan.php');
    }
}

$pageTitle  = 'GrooveVault — Log In';
$navVariant = 'guest';
require_once('inc/header.inc.php');
?>

<div class="view" style="min-height:100vh;">
          <div class="modal-stage">
            <form method="post" action="login.php" class="gv-modal-card p-4">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <h5 class="modal-title-gv">WELCOME <span class="text-blue">BACK</span></h5>
                <a href="index.php" class="bi bi-x-lg text-decoration-none" style="color:var(--text-muted);"></a>
              </div>
              <?= flash_render() ?>
              <?php foreach ($errors as $err): ?>
                <div class="gv-alert gv-alert-danger mb-2" style="font-size:.82rem;"><?= e($err) ?></div>
              <?php endforeach; ?>
              <?= csrf_field() ?>
              <div class="mb-3"><label class="form-label">Email Address</label><input name="email" type="email" class="form-control" placeholder="you@example.com" value="<?= e($old['email']) ?>" required></div>
              <div class="mb-2"><label class="form-label">Password</label><input name="password" class="form-control" type="password" placeholder="Your password" required></div>
              <div class="text-end mb-3"><a href="forgot_password.php" class="text-blue text-decoration-none" style="font-size:.82rem;">Forgot password?</a></div>
              <button type="submit" class="btn btn-gv-primary w-100">Log In</button>
              <p class="text-center mt-3 mb-0" style="font-size:.85rem;color:var(--text-muted);">Don't have an account? <a href="register.php" class="text-pink text-decoration-none">Sign up free</a></p>
            </form>
          </div>
        </div>
<?php
require_once('inc/footer.inc.php');
?>
