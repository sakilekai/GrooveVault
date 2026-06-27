<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name  = trim($_POST['display_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name === '') {
            flash_set('error', 'Display name is required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Please enter a valid email address.');
        } else {
            // Email must stay unique across other accounts.
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                flash_set('error', 'That email is already in use by another account.');
            } else {
                db()->prepare('UPDATE users SET display_name = ?, email = ? WHERE id = ?')
                    ->execute([$name, $email, $user['id']]);
                flash_set('success', 'Profile updated.');
            }
        }
        redirect('account.php');
    }

    if ($action === 'password') {
        $cur     = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        // Re-fetch the hash directly (current_user() doesn't expose it reliably for verify).
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($cur, $hash)) {
            flash_set('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash_set('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            flash_set('error', 'New passwords do not match.');
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_BCRYPT), $user['id']]);
            flash_set('success', 'Password changed.');
        }
        redirect('account.php');
    }
}

$channelsUsed = user_channel_count((int)$user['id']);
$channelLimit = user_channel_limit($user);
$limitLabel   = $channelLimit === PHP_INT_MAX ? 'Unlimited' : (string)$channelLimit;

// Most recent subscription for billing details (if any).
$sub = null;
if (!empty($user['plan_id'])) {
    $stmt = db()->prepare('SELECT * FROM subscriptions WHERE user_id = ? ORDER BY started_at DESC LIMIT 1');
    $stmt->execute([$user['id']]);
    $sub = $stmt->fetch() ?: null;
}

$pageTitle  = 'GrooveVault — Account Settings';
$navVariant = 'user';
require_once('inc/header.inc.php');
?>

<div class="container" style="padding-top:6rem;padding-bottom:3rem;max-width:960px;">
  <div class="mb-3">
    <h2 class="section-title mb-0">ACCOUNT <span class="text-blue">SETTINGS</span></h2>
    <p style="color:var(--text-muted);font-size:.88rem;margin:0;">Manage your profile, password and plan.</p>
  </div>
  <?= flash_render() ?>

  <div class="row g-4">
    <!-- Account overview -->
    <div class="col-lg-5">
      <div class="gv-modal-card p-4" style="max-width:none;">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:54px;height:54px;border-radius:14px;background:linear-gradient(135deg,var(--hot-pink),var(--sunset-orange));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;color:#fff;"><?= e(initials($user['display_name'])) ?></div>
          <div>
            <div style="font-weight:600;"><?= e($user['display_name']) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted);"><?= e($user['email']) ?></div>
          </div>
        </div>

        <div class="gv-divider"></div>

        <div class="d-flex justify-content-between mb-2" style="font-size:.85rem;">
          <span style="color:var(--text-muted);">Plan</span>
          <strong class="text-neon"><?= e($user['plan_name'] ?: 'No plan yet') ?></strong>
        </div>
        <?php if (!empty($user['plan_name'])): ?>
        <div class="d-flex justify-content-between mb-2" style="font-size:.85rem;">
          <span style="color:var(--text-muted);">Price</span>
          <span>$<?= e(number_format((float)$user['plan_price'], 2)) ?><?= $user['plan_billing'] === 'annual' ? '/yr' : '/mo' ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between mb-2" style="font-size:.85rem;">
          <span style="color:var(--text-muted);">Channels used</span>
          <span><?= $channelsUsed ?> / <?= e($limitLabel) ?></span>
        </div>
        <?php if ($sub): ?>
        <div class="d-flex justify-content-between mb-2" style="font-size:.85rem;">
          <span style="color:var(--text-muted);">Member since</span>
          <span><?= e(date('M j, Y', strtotime($sub['started_at']))) ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between mb-2" style="font-size:.85rem;">
          <span style="color:var(--text-muted);">Registered</span>
          <span><?= e(date('M j, Y', strtotime($user['created_at']))) ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2" style="font-size:.85rem;">
          <span style="color:var(--text-muted);">Last login</span>
          <span><?= e(time_ago($user['last_login_at'])) ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2" style="font-size:.85rem;">
          <span style="color:var(--text-muted);">Email verified</span>
          <span><?= $user['email_verified'] ? '<span class="text-neon"><i class="bi bi-check-circle-fill"></i> Verified</span>' : '<span class="text-orange">Not verified</span>' ?></span>
        </div>
        <div class="d-flex justify-content-between mb-3" style="font-size:.85rem;">
          <span style="color:var(--text-muted);">Status</span>
          <span style="text-transform:capitalize;"><?= e($user['status']) ?></span>
        </div>

        <a href="pick_plan.php" class="btn btn-gv-blue btn-sm w-100"><i class="bi bi-arrow-up-circle me-1"></i><?= empty($user['plan_id']) ? 'Choose a plan' : 'Change plan' ?></a>
      </div>
    </div>

    <!-- Edit forms -->
    <div class="col-lg-7">
      <div class="gv-modal-card p-4 mb-4" style="max-width:none;">
        <h6 style="font-weight:600;margin-bottom:1rem;">Profile Details</h6>
        <form method="post" action="account.php">
          <?= csrf_field() ?>
          <div class="mb-3"><label class="form-label">Display Name</label><input name="display_name" class="form-control" value="<?= e($user['display_name']) ?>" required></div>
          <div class="mb-3"><label class="form-label">Email Address</label><input name="email" type="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
          <div class="text-end"><button name="action" value="profile" class="btn btn-gv-primary btn-sm"><i class="bi bi-check2 me-1"></i>Save Changes</button></div>
        </form>
      </div>

      <div class="gv-modal-card p-4" style="max-width:none;">
        <h6 style="font-weight:600;margin-bottom:1rem;">Change Password</h6>
        <form method="post" action="account.php">
          <?= csrf_field() ?>
          <div class="mb-3"><label class="form-label">Current Password</label><input name="current_password" type="password" class="form-control" placeholder="••••••••" required></div>
          <div class="mb-3"><label class="form-label">New Password</label><input name="new_password" type="password" class="form-control" placeholder="Min. 8 characters" required></div>
          <div class="mb-3"><label class="form-label">Confirm New Password</label><input name="confirm_password" type="password" class="form-control" placeholder="Repeat new password" required></div>
          <div class="text-end"><button name="action" value="password" class="btn btn-gv-primary btn-sm"><i class="bi bi-shield-lock me-1"></i>Update Password</button></div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
require_once('inc/footer.inc.php');
?>
