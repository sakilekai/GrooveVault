<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();

/* Manually create a new user from the admin panel.
   Admin-created accounts are pre-verified and active so the user can log in
   right away with the password the admin sets. On error we re-render the page
   with the modal re-opened (see $createErrors below). */
$createErrors = [];
$createOld    = ['display_name' => '', 'email' => '', 'plan_id' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    verify_csrf();
    $name   = trim($_POST['display_name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $planId = (int)($_POST['plan_id'] ?? 0);
    $createOld['display_name'] = $name;
    $createOld['email']        = $email;
    $createOld['plan_id']      = $planId ?: '';

    if ($name === '')                               $createErrors[] = 'Display name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $createErrors[] = 'A valid email address is required.';
    if (strlen($pass) < 8)                          $createErrors[] = 'Password must be at least 8 characters.';

    if (!$createErrors && $planId > 0) {
        $chk = db()->prepare('SELECT id FROM plans WHERE id = ?');
        $chk->execute([$planId]);
        if (!$chk->fetch()) $createErrors[] = 'The selected plan no longer exists.';
    }

    if (!$createErrors) {
        $dup = db()->prepare('SELECT id FROM users WHERE email = ?');
        $dup->execute([$email]);
        if ($dup->fetch()) $createErrors[] = 'An account with that email already exists.';
    }

    if (!$createErrors) {
        $ins = db()->prepare(
            "INSERT INTO users (display_name, email, password_hash, email_verified, plan_id, status)
             VALUES (?, ?, ?, 1, ?, 'active')"
        );
        $ins->execute([$name, $email, password_hash($pass, PASSWORD_BCRYPT), $planId ?: null]);
        gv_log('create_user', 'Created user ' . $name . ' (' . $email . ')');
        flash_set('success', 'User "' . $name . '" was created.');
        redirect('users.php');
    }
    // Validation failed: fall through and render the list with the modal open.
}

$q    = trim($_GET['q'] ?? '');
$plan = trim($_GET['plan'] ?? '');

// Build the filtered query.
$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(u.display_name LIKE ? OR u.email LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($plan !== '') {
    if ($plan === 'none') { $where[] = 'u.plan_id IS NULL'; }
    else { $where[] = 'p.code = ?'; $params[] = $plan; }
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT u.*, p.code AS plan_code, p.name AS plan_name, p.price AS plan_price, p.billing_period AS plan_billing
        FROM users u LEFT JOIN plans p ON p.id = u.plan_id
        $whereSql ORDER BY u.created_at DESC";

// CSV export (before any output).
if (($_GET['export'] ?? '') === 'csv') {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    gv_log('export', 'Exported users CSV');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="groovevault-users.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Email', 'Plan', 'Price', 'Registered', 'Last login', 'Status']);
    foreach ($stmt->fetchAll() as $u) {
        $price = $u['plan_price'] !== null
            ? '$' . number_format((float)$u['plan_price'], 2) . ($u['plan_billing'] === 'annual' ? '/yr' : '/mo')
            : '';
        fputcsv($out, [
            $u['display_name'], $u['email'], $u['plan_name'] ?: 'None',
            $price, $u['created_at'], $u['last_login_at'] ?: '', $u['status'],
        ]);
    }
    fclose($out);
    exit;
}

$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$plans = db()->query('SELECT id, code, name FROM plans ORDER BY sort_order')->fetchAll();

$avatarColors = ['linear-gradient(135deg,#5B6EF5,#8B5CF6)','linear-gradient(135deg,#38BDF8,#0077FF)','linear-gradient(135deg,#22D78A,#00A800)','linear-gradient(135deg,#F5456B,#FF6B00)','linear-gradient(135deg,#F5A623,#FF6B00)'];

$pageTitle   = 'GrooveVault — Users';
$adminActive = 'users';
$topbarTitle = 'Users';
require_once __DIR__ . '/../inc/admin-header.inc.php';

// Preserve current filters when building the export link.
$exportQs = http_build_query(array_filter(['q' => $q, 'plan' => $plan]) + ['export' => 'csv']);
?>

    <form method="get" action="users.php" class="section-header">
      <div class="d-flex gap-2 flex-wrap">
        <div class="search-wrap"><i class="bi bi-search"></i><input name="q" class="admin-search" placeholder="Search name or email" value="<?= e($q) ?>"></div>
        <select name="plan" class="filter-select" onchange="this.form.submit()">
          <option value="">All plans</option>
          <?php foreach ($plans as $p): ?>
            <option value="<?= e($p['code']) ?>"<?= $plan === $p['code'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
          <?php endforeach; ?>
          <option value="none"<?= $plan === 'none' ? ' selected' : '' ?>>No plan</option>
        </select>
        <button type="submit" class="btn-admin btn-admin-ghost">Filter</button>
      </div>
      <div class="d-flex gap-2">
        <a href="users.php?<?= e($exportQs) ?>" class="btn-admin btn-admin-ghost text-decoration-none"><i class="bi bi-download me-1"></i>Export CSV</a>
        <button type="button" class="btn-admin btn-admin-primary" onclick="gvOpenAddUser()"><i class="bi bi-person-plus me-1"></i>Add User</button>
      </div>
    </form>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>User</th><th>Email</th><th>Plan</th><th>Price paid</th><th>Registered</th><th>Last login</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (!$users): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No users match your filters.</td></tr>
          <?php endif; ?>
          <?php foreach ($users as $i => $u):
            $badge = $u['plan_code'] ?: 'none';
            $price = $u['plan_price'] !== null
                ? '$' . number_format((float)$u['plan_price'], 2) . ($u['plan_billing'] === 'annual' ? '/yr' : '/mo')
                : '—';
          ?>
          <tr>
            <td><div class="d-flex align-items-center gap-2"><div class="user-avatar-sm" style="background:<?= $avatarColors[$i % count($avatarColors)] ?>;"><?= e(initials($u['display_name'])) ?></div><a href="user_detail.php?id=<?= (int)$u['id'] ?>" class="text-decoration-none text-reset"><?= e($u['display_name']) ?></a></div></td>
            <td style="color:var(--text-dim);"><?= e($u['email']) ?></td>
            <td><span class="plan-badge <?= e($badge) ?>"><?= e($u['plan_name'] ?: 'None') ?></span></td>
            <td style="font-family:'Space Mono',monospace;color:<?= $price === '—' ? 'var(--text-muted)' : 'var(--accent-green)' ?>;"><?= e($price) ?></td>
            <td style="color:var(--text-dim);"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
            <td style="color:var(--text-dim);"><?= e(time_ago($u['last_login_at'])) ?></td>
            <td><span class="status-dot <?= $u['status'] === 'active' ? 'active' : 'suspended' ?>"></span><?= ucfirst($u['status']) ?></td>
            <td><a href="user_detail.php?id=<?= (int)$u['id'] ?>" class="text-decoration-none"><i class="bi bi-three-dots" style="color:var(--text-muted);"></i></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
      <span style="font-size:.78rem;color:var(--text-muted);">Showing <?= count($users) ?> user<?= count($users) === 1 ? '' : 's' ?></span>
    </div>

    <!-- Add User modal -->
    <div id="addUserModal" class="gv-admin-overlay"<?= $createErrors ? ' style="display:flex;"' : '' ?>>
      <div class="admin-modal-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="admin-card-title" style="margin:0;">Add New User</div>
          <button type="button" onclick="gvCloseAddUser()" aria-label="Close" style="background:none;border:none;color:var(--text-muted);font-size:1.3rem;line-height:1;cursor:pointer;">&times;</button>
        </div>
        <?php if ($createErrors): ?>
          <div style="background:rgba(245,69,107,0.1);border:1px solid var(--accent-pink);color:var(--accent-pink);border-radius:10px;padding:.6rem .9rem;font-size:.82rem;margin-bottom:1rem;">
            <?php foreach ($createErrors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>
        <form method="post" action="users.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create">
          <div class="mb-3"><label class="form-label">Display name</label><input name="display_name" class="form-control" value="<?= e($createOld['display_name']) ?>" required></div>
          <div class="mb-3"><label class="form-label">Email address</label><input name="email" type="email" class="form-control" value="<?= e($createOld['email']) ?>" required></div>
          <div class="mb-3"><label class="form-label">Temporary password</label><input name="password" type="text" class="form-control" placeholder="At least 8 characters" minlength="8" required></div>
          <div class="mb-3"><label class="form-label">Plan (optional)</label>
            <select name="plan_id" class="form-select">
              <option value="">No plan</option>
              <?php foreach ($plans as $p): ?>
                <option value="<?= (int)$p['id'] ?>"<?= (string)$createOld['plan_id'] === (string)$p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <p style="font-size:.74rem;color:var(--text-muted);margin-bottom:1rem;"><i class="bi bi-info-circle me-1"></i>The account is created verified and active — the user can log in immediately with this password.</p>
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn-admin btn-admin-ghost" onclick="gvCloseAddUser()">Cancel</button>
            <button type="submit" class="btn-admin btn-admin-primary"><i class="bi bi-person-plus me-1"></i>Create User</button>
          </div>
        </form>
      </div>
    </div>
    <script>
      function gvOpenAddUser() { var m = document.getElementById('addUserModal'); if (m) { m.style.display = 'flex'; var f = m.querySelector('input[name=display_name]'); if (f) f.focus(); } }
      function gvCloseAddUser() { var m = document.getElementById('addUserModal'); if (m) m.style.display = 'none'; }
      (function () {
        var m = document.getElementById('addUserModal');
        if (m) m.addEventListener('click', function (e) { if (e.target === this) gvCloseAddUser(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') gvCloseAddUser(); });
      })();
    </script>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
