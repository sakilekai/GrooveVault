<?php
require_once __DIR__ . '/../inc/functions.inc.php';

if (current_admin()) redirect('dashboard.php');

$errors = [];
$old    = ['username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $old['username'] = $username;

    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        $errors[] = 'Invalid username or password.';
    } else {
        $_SESSION['admin_id'] = (int)$admin['id'];
        db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')->execute([$admin['id']]);
        gv_log('login', 'Admin signed in', (int)$admin['id']);
        redirect('dashboard.php');
    }
}

$pageTitle = 'GrooveVault — Admin Login';
$adminBare = true;
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

  <div class="modal-stage">
    <form method="post" action="login.php" class="login-box">
      <div class="text-center mb-4">
        <div class="logo-text mb-1">GrooveVault</div>
        <div style="font-size:.85rem;color:var(--text-muted);">Admin Control Center</div>
      </div>
      <?php foreach ($errors as $err): ?>
        <div style="background:rgba(245,69,107,0.1);border:1px solid var(--accent-pink);color:var(--accent-pink);border-radius:10px;padding:.6rem .9rem;font-size:.82rem;margin-bottom:1rem;"><?= e($err) ?></div>
      <?php endforeach; ?>
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Admin Username</label><input name="username" class="form-control" value="<?= e($old['username']) ?>" required autofocus></div>
      <div class="mb-4"><label class="form-label">Password</label><input name="password" class="form-control" type="password" placeholder="••••••••" required></div>
      <button type="submit" class="btn-admin btn-admin-primary w-100" style="padding:.65rem;font-size:.95rem;border-radius:10px;"><i class="bi bi-shield-lock me-2"></i>Access Admin Panel</button>
    </form>
  </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
