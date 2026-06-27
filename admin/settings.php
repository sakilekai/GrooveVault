<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();
$admin = current_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'password') {
        $cur     = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($cur, $admin['password_hash'])) {
            flash_set('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 6) {
            flash_set('error', 'New password must be at least 6 characters.');
        } elseif ($new !== $confirm) {
            flash_set('error', 'New passwords do not match.');
        } else {
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_BCRYPT), $admin['id']]);
            gv_log('password', 'Admin changed password');
            flash_set('success', 'Admin password updated.');
        }
        redirect('settings.php');
    }

    if ($action === 'controls') {
        set_setting('allow_signups',          isset($_POST['allow_signups']) ? '1' : '0');
        set_setting('maintenance_mode',       isset($_POST['maintenance_mode']) ? '1' : '0');
        set_setting('max_track_duration_min', (string)max(1, (int)($_POST['max_track_duration_min'] ?? 10)));
        set_setting('max_tracks_per_channel', (string)max(1, (int)($_POST['max_tracks_per_channel'] ?? 12)));
        set_setting('starter_channel_limit',  (string)max(1, (int)($_POST['starter_channel_limit'] ?? 5)));
        gv_log('settings', 'Updated platform controls');
        flash_set('success', 'Platform controls saved.');
        redirect('settings.php');
    }

    if ($action === 'paypal') {
        set_setting('paypal_client_id',     trim($_POST['paypal_client_id'] ?? ''));
        set_setting('paypal_client_secret', trim($_POST['paypal_client_secret'] ?? ''));
        set_setting('paypal_mode',          ($_POST['paypal_mode'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox');
        gv_log('settings', 'Updated PayPal settings');
        flash_set('success', 'PayPal settings saved.');
        redirect('settings.php');
    }

    if ($action === 'purge') {
        if (($_POST['confirm_text'] ?? '') === 'PURGE') {
            $pdo = db();
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['tracks', 'channels', 'subscriptions', 'password_resets', 'users'] as $t) {
                $pdo->exec("DELETE FROM $t");
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            gv_log('purge', 'Purged all user data');
            flash_set('success', 'All user data has been purged.');
        } else {
            flash_set('error', 'Purge cancelled — confirmation text did not match.');
        }
        redirect('settings.php');
    }
}

$allowSignups = setting('allow_signups', '1') === '1';
$maintenance  = setting('maintenance_mode', '0') === '1';
$maxDur       = (int)setting('max_track_duration_min', 10);
$maxTracks    = (int)setting('max_tracks_per_channel', 12);
$starterLimit = (int)setting('starter_channel_limit', 5);
$paypalClientId     = setting('paypal_client_id', '');
$paypalClientSecret = setting('paypal_client_secret', '');
$paypalMode         = setting('paypal_mode', 'sandbox');
$paypalReady        = $paypalClientId !== '' && $paypalClientSecret !== '';

$pageTitle   = 'GrooveVault — Admin Settings';
$adminActive = 'settings';
$topbarTitle = 'Admin Settings';
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

    <div class="section-header"><div class="section-title">Admin Settings</div></div>
    <div class="row g-3">
      <div class="col-md-6"><div class="admin-card">
        <div class="admin-card-title">Change Admin Password</div>
        <form method="post" action="settings.php">
          <?= csrf_field() ?>
          <div class="mb-2"><label class="form-label">Current Password</label><input name="current_password" class="form-control" type="password" placeholder="••••••••" required></div>
          <div class="mb-2"><label class="form-label">New Password</label><input name="new_password" class="form-control" type="password" placeholder="Min. 6 characters" required></div>
          <div class="mb-3"><label class="form-label">Confirm New Password</label><input name="confirm_password" class="form-control" type="password" placeholder="Repeat" required></div>
          <button name="action" value="password" class="btn-admin btn-admin-primary">Update Password</button>
        </form>
      </div></div>
      <div class="col-md-6"><div class="admin-card">
        <div class="admin-card-title">Platform Controls</div>
        <form method="post" action="settings.php">
          <?= csrf_field() ?>
          <div class="d-flex justify-content-between align-items-center mb-3"><span style="font-size:.85rem;">Allow new sign-ups</span><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_signups"<?= $allowSignups ? ' checked' : '' ?> style="background-color:<?= $allowSignups ? 'var(--accent)' : '' ?>;border-color:var(--accent);"></div></div>
          <div class="d-flex justify-content-between align-items-center mb-3"><span style="font-size:.85rem;">Maintenance mode</span><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="maintenance_mode"<?= $maintenance ? ' checked' : '' ?>></div></div>
          <div class="d-flex justify-content-between align-items-center mb-3"><span style="font-size:.85rem;">Max track duration</span>
            <select name="max_track_duration_min" class="filter-select" style="width:auto;">
              <?php foreach ([5, 10, 15, 20] as $m): ?><option value="<?= $m ?>"<?= $maxDur === $m ? ' selected' : '' ?>><?= $m ?> min</option><?php endforeach; ?>
            </select></div>
          <div class="d-flex justify-content-between align-items-center mb-3"><span style="font-size:.85rem;">Max tracks per channel</span>
            <select name="max_tracks_per_channel" class="filter-select" style="width:auto;">
              <?php foreach ([6, 12, 18, 24] as $m): ?><option value="<?= $m ?>"<?= $maxTracks === $m ? ' selected' : '' ?>><?= $m ?></option><?php endforeach; ?>
            </select></div>
          <div class="d-flex justify-content-between align-items-center mb-3"><span style="font-size:.85rem;">Starter channel limit</span>
            <select name="starter_channel_limit" class="filter-select" style="width:auto;">
              <?php foreach ([3, 5, 10, 15] as $m): ?><option value="<?= $m ?>"<?= $starterLimit === $m ? ' selected' : '' ?>><?= $m ?></option><?php endforeach; ?>
            </select></div>
          <button name="action" value="controls" class="btn-admin btn-admin-primary">Save Controls</button>
        </form>
        <div class="divider"></div>
        <div class="stat-label" style="color:var(--accent-pink);margin-bottom:.5rem;">Danger Zone</div>
        <form method="post" action="settings.php" onsubmit="return confirm('This permanently deletes ALL users, channels, tracks and subscriptions. Continue?');">
          <?= csrf_field() ?>
          <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:.4rem;">Type <code style="color:var(--accent-pink);">PURGE</code> to confirm:</p>
          <input name="confirm_text" class="form-control mb-2" placeholder="PURGE" style="max-width:160px;">
          <button name="action" value="purge" class="btn-admin btn-admin-danger"><i class="bi bi-exclamation-triangle me-1"></i>Purge All Data</button>
        </form>
      </div></div>
      <div class="col-md-6"><div class="admin-card">
        <div class="admin-card-title">PayPal Checkout</div>
        <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:1rem;">
          Plans are managed under <a href="plans.php" style="color:var(--accent-blue);">Plans</a>.
          Prices there are charged automatically at checkout via PayPal.
        </p>
        <?php if ($paypalReady): ?>
          <div style="background:rgba(34,215,138,0.1);border:1px solid var(--accent-green);color:var(--accent-green);border-radius:8px;padding:.5rem .8rem;font-size:.8rem;margin-bottom:1rem;">
            <i class="bi bi-check-circle me-1"></i> PayPal configured (<?= e($paypalMode) ?> mode)
          </div>
        <?php else: ?>
          <div style="background:rgba(245,69,107,0.1);border:1px solid var(--accent-pink);color:var(--accent-pink);border-radius:8px;padding:.5rem .8rem;font-size:.8rem;margin-bottom:1rem;">
            PayPal not configured — users cannot complete checkout.
          </div>
        <?php endif; ?>
        <form method="post" action="settings.php">
          <?= csrf_field() ?>
          <div class="mb-2">
            <label class="form-label">Client ID</label>
            <input name="paypal_client_id" class="form-control" value="<?= e($paypalClientId) ?>" placeholder="Sandbox or Live Client ID">
          </div>
          <div class="mb-2">
            <label class="form-label">Client Secret</label>
            <input name="paypal_client_secret" class="form-control" type="password" value="<?= e($paypalClientSecret) ?>" placeholder="PayPal app secret" autocomplete="new-password">
          </div>
          <div class="mb-3">
            <label class="form-label">Mode</label>
            <select name="paypal_mode" class="form-control">
              <option value="sandbox"<?= $paypalMode !== 'live' ? ' selected' : '' ?>>Sandbox (testing)</option>
              <option value="live"<?= $paypalMode === 'live' ? ' selected' : '' ?>>Live (real payments)</option>
            </select>
          </div>
          <p style="font-size:.72rem;color:var(--text-muted);margin-bottom:.8rem;">
            Get credentials from
            <a href="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank" rel="noopener" style="color:var(--accent-blue);">PayPal Developer Dashboard</a>
            → Create App → copy Client ID &amp; Secret.
          </p>
          <button name="action" value="paypal" class="btn-admin btn-admin-primary">Save PayPal Settings</button>
        </form>
      </div></div>
    </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
