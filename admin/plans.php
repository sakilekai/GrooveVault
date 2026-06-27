<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id        = (int)($_POST['plan_id'] ?? 0);
        $code      = strtolower(trim($_POST['code'] ?? ''));
        $name      = trim($_POST['name'] ?? '');
        $price     = $_POST['price'] ?? '';
        $billing   = ($_POST['billing_period'] ?? 'monthly') === 'annual' ? 'annual' : 'monthly';
        $unlimited = !empty($_POST['unlimited']);
        $limit     = $unlimited ? null : (int)($_POST['channel_limit'] ?? 0);
        $popular   = !empty($_POST['is_popular']) ? 1 : 0;
        $sort      = (int)($_POST['sort_order'] ?? 0);

        if ($code === '' || !preg_match('/^[a-z0-9_]+$/', $code)) $errors[] = 'Code is required (lowercase letters, numbers, underscore).';
        if ($name === '')                                          $errors[] = 'Plan name is required.';
        if (!is_numeric($price) || (float)$price < 0)              $errors[] = 'Enter a valid price.';
        if (!$unlimited && $limit < 1)                             $errors[] = 'Channel limit must be at least 1 (or tick Unlimited).';

        if (!$errors) {
            // Code must stay unique.
            $stmt = db()->prepare('SELECT id FROM plans WHERE code = ? AND id <> ?');
            $stmt->execute([$code, $id]);
            if ($stmt->fetch()) {
                $errors[] = 'Another plan already uses that code.';
            }
        }

        if (!$errors) {
            if ($id) {
                db()->prepare(
                    'UPDATE plans SET code=?, name=?, price=?, billing_period=?, channel_limit=?, is_popular=?, sort_order=? WHERE id=?'
                )->execute([$code, $name, (float)$price, $billing, $limit, $popular, $sort, $id]);
                gv_log('plan', 'Updated plan ' . $name);
                flash_set('success', 'Plan “' . $name . '” updated.');
            } else {
                db()->prepare(
                    'INSERT INTO plans (code, name, price, billing_period, channel_limit, is_popular, sort_order) VALUES (?,?,?,?,?,?,?)'
                )->execute([$code, $name, (float)$price, $billing, $limit, $popular, $sort]);
                gv_log('plan', 'Created plan ' . $name);
                flash_set('success', 'Plan “' . $name . '” created.');
            }
            redirect('plans.php');
        }
        // fall through to render with $errors (and repopulate from POST)
    }

    if ($action === 'delete') {
        $id = (int)($_POST['plan_id'] ?? 0);
        $stmt = db()->prepare('SELECT name FROM plans WHERE id = ?');
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        if ($name !== false) {
            try {
                db()->prepare('DELETE FROM plans WHERE id = ?')->execute([$id]);
                gv_log('plan', 'Deleted plan ' . $name);
                flash_set('success', 'Plan “' . $name . '” deleted.');
            } catch (PDOException $e) {
                // subscriptions.plan_id is RESTRICT — can't delete a plan in use.
                flash_set('error', 'Cannot delete “' . $name . '” — it has subscription records. Reassign those first.');
            }
        }
        redirect('plans.php');
    }
}

// Which plan is being edited / created (drives the side form).
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$isNew  = isset($_GET['new']);
$edit   = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM plans WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}

// Repopulate the form from POST when validation failed.
$f = [
    'plan_id'        => $_POST['plan_id'] ?? ($edit['id'] ?? 0),
    'code'           => $_POST['code'] ?? ($edit['code'] ?? ''),
    'name'           => $_POST['name'] ?? ($edit['name'] ?? ''),
    'price'          => $_POST['price'] ?? ($edit['price'] ?? ''),
    'billing_period' => $_POST['billing_period'] ?? ($edit['billing_period'] ?? 'monthly'),
    'channel_limit'  => $_POST['channel_limit'] ?? ($edit['channel_limit'] ?? ''),
    'unlimited'      => isset($_POST['action']) ? !empty($_POST['unlimited']) : ($edit ? $edit['channel_limit'] === null : false),
    'is_popular'     => isset($_POST['action']) ? !empty($_POST['is_popular']) : (bool)($edit['is_popular'] ?? false),
    'sort_order'     => $_POST['sort_order'] ?? ($edit['sort_order'] ?? 0),
];
$showForm = $editId || $isNew || $errors;

$plans = db()->query(
    'SELECT p.*, (SELECT COUNT(*) FROM users u WHERE u.plan_id = p.id) AS users_on_plan
     FROM plans p ORDER BY p.sort_order, p.id'
)->fetchAll();

$pageTitle   = 'GrooveVault — Plans';
$adminActive = 'plans';
$topbarTitle = 'Plans';
$topbarRight = '<a href="plans.php?new=1" class="btn-admin btn-admin-primary text-decoration-none"><i class="bi bi-plus-lg me-1"></i>New Plan</a><div class="admin-avatar">A</div>';
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

    <div class="row g-3">
      <div class="col-12">
        <div style="background:rgba(56,189,248,0.08);border:1px solid rgba(56,189,248,0.3);color:var(--accent-blue);border-radius:10px;padding:.7rem 1rem;font-size:.82rem;margin-bottom:.5rem;">
          <i class="bi bi-info-circle me-1"></i>
          Plans here appear automatically on the homepage pricing section and on user checkout. Prices are charged via PayPal when users pick a plan.
        </div>
      </div>
      <div class="<?= $showForm ? 'col-lg-7' : 'col-12' ?>">
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Plan</th><th>Code</th><th>Price</th><th>Channels</th><th>Users</th><th>Popular</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($plans as $p): ?>
              <tr>
                <td><strong><?= e($p['name']) ?></strong></td>
                <td><span class="plan-badge <?= e($p['code']) ?>"><?= e($p['code']) ?></span></td>
                <td style="font-family:'Space Mono',monospace;color:var(--accent-green);">$<?= number_format((float)$p['price'], 2) ?><?= $p['billing_period'] === 'annual' ? '/yr' : '/mo' ?></td>
                <td style="color:var(--text-dim);"><?= $p['channel_limit'] === null ? 'Unlimited' : (int)$p['channel_limit'] ?></td>
                <td style="color:var(--text-dim);"><?= (int)$p['users_on_plan'] ?></td>
                <td><?= $p['is_popular'] ? '<i class="bi bi-star-fill" style="color:var(--accent-amber);"></i>' : '<span style="color:var(--text-muted);">—</span>' ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="plans.php?edit=<?= (int)$p['id'] ?>" class="btn-admin btn-admin-ghost text-decoration-none"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="plans.php" style="margin:0;" onsubmit="return confirm('Delete the “<?= e($p['name']) ?>” plan?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>">
                      <button name="action" value="delete" class="btn-admin btn-admin-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if ($showForm): ?>
      <div class="col-lg-5">
        <div class="admin-card">
          <div class="admin-card-title"><?= $f['plan_id'] ? 'Edit Plan' : 'New Plan' ?></div>
          <?php foreach ($errors as $err): ?>
            <div style="background:rgba(245,69,107,0.1);border:1px solid var(--accent-pink);color:var(--accent-pink);border-radius:8px;padding:.5rem .8rem;font-size:.8rem;margin-bottom:.8rem;"><?= e($err) ?></div>
          <?php endforeach; ?>
          <form method="post" action="plans.php">
            <?= csrf_field() ?>
            <input type="hidden" name="plan_id" value="<?= (int)$f['plan_id'] ?>">
            <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" value="<?= e($f['name']) ?>" placeholder="Groove Pro" required></div>
            <div class="mb-2"><label class="form-label">Code <small style="text-transform:none;color:var(--text-muted);">(unique id)</small></label><input name="code" class="form-control" value="<?= e($f['code']) ?>" placeholder="pro" required></div>
            <div class="row g-2">
              <div class="col-6 mb-2"><label class="form-label">Price</label><input name="price" type="number" step="0.01" min="0" class="form-control" value="<?= e($f['price']) ?>" placeholder="9.99" required></div>
              <div class="col-6 mb-2"><label class="form-label">Billing</label>
                <select name="billing_period" class="form-control">
                  <option value="monthly"<?= $f['billing_period'] === 'monthly' ? ' selected' : '' ?>>Monthly</option>
                  <option value="annual"<?= $f['billing_period'] === 'annual' ? ' selected' : '' ?>>Annual</option>
                </select>
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label">Channel limit</label>
              <input name="channel_limit" type="number" min="1" class="form-control" value="<?= e($f['unlimited'] ? '' : $f['channel_limit']) ?>" placeholder="5"<?= $f['unlimited'] ? ' disabled' : '' ?> id="climit">
              <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="unlimited" id="unlimited"<?= $f['unlimited'] ? ' checked' : '' ?> onchange="document.getElementById('climit').disabled=this.checked;"><label class="form-check-label" for="unlimited" style="font-size:.82rem;">Unlimited channels</label></div>
            </div>
            <div class="row g-2">
              <div class="col-6 mb-2"><label class="form-label">Sort order</label><input name="sort_order" type="number" class="form-control" value="<?= e($f['sort_order']) ?>"></div>
              <div class="col-6 mb-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_popular" id="pop"<?= $f['is_popular'] ? ' checked' : '' ?>><label class="form-check-label" for="pop" style="font-size:.82rem;">Mark “Popular”</label></div></div>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-2">
              <a href="plans.php" class="btn-admin btn-admin-ghost text-decoration-none">Cancel</a>
              <button name="action" value="save" class="btn-admin btn-admin-primary"><i class="bi bi-check2 me-1"></i>Save Plan</button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
