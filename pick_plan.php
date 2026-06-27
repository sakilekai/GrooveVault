<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_once __DIR__ . '/inc/paypal.inc.php';
require_login();
$user = current_user();

$plans = db()->query('SELECT * FROM plans ORDER BY sort_order, id')->fetchAll();

// How many channels the user already has — used to flag plans they'd exceed.
$channelCount = user_channel_count((int)$user['id']);

// Default selection: popular plan, else first plan.
$defaultPlanId = null;
$defaultPlanName = '';
foreach ($plans as $p) {
    if ($p['is_popular']) {
        $defaultPlanId = (int)$p['id'];
        $defaultPlanName = $p['name'];
        break;
    }
}
if ($defaultPlanId === null && $plans) {
    $defaultPlanId = (int)$plans[0]['id'];
    $defaultPlanName = $plans[0]['name'];
}

$paypalReady = paypal_configured();

$pageTitle  = 'GrooveVault — Pick Plan + PayPal';
$navVariant = 'user';
require_once('inc/header.inc.php');
?>

<div class="view" style="min-height:100vh;">
  <div class="modal-stage">
    <div class="gv-modal-card p-4" id="planForm">
      <h5 class="modal-title-gv mb-3">CHOOSE YOUR <span class="text-pink">PLAN</span></h5>
      <?= flash_render() ?>
      <?php if (!empty($_SESSION['plan_expired'])): $ep = $_SESSION['plan_expired']; unset($_SESSION['plan_expired']); ?>
        <div class="gv-alert gv-alert-danger mb-3" style="font-size:.85rem;">
          <i class="bi bi-exclamation-circle me-1"></i>Your <?= e($ep) ?> subscription has expired. Choose a plan and pay again to continue using your channels.
        </div>
      <?php endif; ?>

      <?php if (!$plans): ?>
        <div class="gv-alert gv-alert-info">No plans are available yet. Please contact support.</div>
      <?php else: ?>

      <input type="hidden" id="planIdField" value="<?= e($defaultPlanId) ?>">
      <input type="hidden" id="csrfField" value="<?= e(csrf_token()) ?>">

      <?php foreach ($plans as $plan):
        $per     = $plan['billing_period'] === 'annual' ? '/yr' : '/mo';
        $sub     = $plan['channel_limit'] === null ? 'Unlimited channels' : ('Up to ' . (int)$plan['channel_limit'] . ' channels');
        $selected = (int)$plan['id'] === $defaultPlanId;
        $limit   = plan_channel_limit($plan);
        $need    = ($limit !== PHP_INT_MAX && $channelCount > $limit) ? ($channelCount - $limit) : 0;
      ?>
      <label class="plan-option d-block<?= $selected ? ' selected' : '' ?>" style="cursor:pointer;"
             data-plan="<?= e($plan['id']) ?>"
             data-price="<?= e(number_format((float)$plan['price'], 2, '.', '')) ?>"
             data-name="<?= e($plan['name']) ?>"
             data-need="<?= (int)$need ?>">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <?php if ($plan['is_popular']): ?><span class="badge-gv-pink badge-gv">POPULAR</span><br><?php endif; ?>
            <strong><?= e($plan['name']) ?></strong><br>
            <small style="color:var(--text-muted);"><?= e($sub) ?></small>
            <?php if ($need > 0): ?>
              <div style="color:var(--hot-pink);font-size:.74rem;margin-top:.35rem;"><i class="bi bi-exclamation-triangle me-1"></i>Delete <?= (int)$need ?> channel<?= $need === 1 ? '' : 's' ?> to switch</div>
            <?php endif; ?>
          </div>
          <div class="plan-price">$<?= e(number_format((float)$plan['price'], 2)) ?><small style="font-size:.9rem;color:var(--text-muted)"><?= e($per) ?></small></div>
        </div>
      </label>
      <?php endforeach; ?>

      <div class="gv-divider" style="margin:1rem 0;"></div>

      <div id="planBlockWarning" class="gv-alert gv-alert-danger" style="display:none;font-size:.82rem;margin-bottom:.9rem;">
        <span id="planBlockMsg"></span>
        <a href="dashboard.php" class="text-decoration-none" style="color:var(--hot-pink);font-weight:600;">Manage channels →</a>
      </div>

      <?php if ($paypalReady): ?>
        <p style="font-size:.84rem;color:var(--text-muted);margin-bottom:.8rem;">
          Selected: <strong id="selectedPlanLabel" style="color:var(--text-main);"><?= e($defaultPlanName) ?></strong>
          — pay securely with PayPal.
        </p>
        <div id="paypal-button-container"></div>
        <div id="paypal-error" class="gv-alert gv-alert-danger mt-2" style="display:none;font-size:.82rem;"></div>
        <p style="font-size:.74rem;color:var(--text-muted);margin-top:.7rem;text-align:center;">
          Plans and prices are managed in the admin panel and charged via PayPal <?= e(paypal_mode()) ?>.
        </p>
      <?php else: ?>
        <div class="gv-alert gv-alert-info" style="font-size:.82rem;">
          PayPal checkout is not configured yet. Admin must add Client ID and Secret in
          <strong>Admin → Settings → PayPal</strong>.
        </div>
      <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($paypalReady && $plans): ?>
<script src="<?= e(paypal_sdk_url()) ?>"></script>
<script>
(function () {
  var csrf = document.getElementById('csrfField').value;

  // Show a warning and hide the PayPal buttons when the chosen plan can't hold
  // the user's current channels (server enforces this too).
  function applyBlock(el) {
    var need = parseInt(el.dataset.need || '0', 10);
    var warn = document.getElementById('planBlockWarning');
    var msg  = document.getElementById('planBlockMsg');
    var cont = document.getElementById('paypal-button-container');
    if (need > 0) {
      if (msg)  msg.textContent = 'The ' + el.dataset.name + ' plan allows fewer channels than you currently have. Delete ' + need + ' channel' + (need === 1 ? '' : 's') + ' first. ';
      if (warn) warn.style.display = 'block';
      if (cont) cont.style.display = 'none';
    } else {
      if (warn) warn.style.display = 'none';
      if (cont) cont.style.display = '';
    }
  }

  document.querySelectorAll('.plan-option').forEach(function (el) {
    el.addEventListener('click', function () {
      document.querySelectorAll('.plan-option').forEach(function (p) { p.classList.remove('selected'); });
      el.classList.add('selected');
      document.getElementById('planIdField').value = el.dataset.plan;
      var label = document.getElementById('selectedPlanLabel');
      if (label) label.textContent = el.dataset.name + ' ($' + el.dataset.price + ')';
      applyBlock(el);
    });
  });

  // Apply to whichever plan is selected on load.
  var initial = document.querySelector('.plan-option.selected');
  if (initial) applyBlock(initial);

  function showError(msg) {
    var box = document.getElementById('paypal-error');
    if (!box) return;
    box.style.display = 'block';
    box.textContent = msg;
  }

  paypal.Buttons({
    style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'paypal' },
    createOrder: function () {
      document.getElementById('paypal-error').style.display = 'none';
      var sel = document.querySelector('.plan-option.selected');
      if (sel && parseInt(sel.dataset.need || '0', 10) > 0) {
        showError('Delete the extra channels before switching to this plan.');
        return Promise.reject(new Error('Over channel limit for selected plan.'));
      }
      return fetch('paypal_create_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          plan_id: document.getElementById('planIdField').value,
          csrf: csrf
        })
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.error) throw new Error(data.error);
        return data.orderID;
      });
    },
    onApprove: function (data) {
      return fetch('paypal_capture_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          orderID: data.orderID,
          plan_id: document.getElementById('planIdField').value,
          csrf: csrf
        })
      })
      .then(function (r) { return r.json(); })
      .then(function (result) {
        if (result.error) throw new Error(result.error);
        window.location.href = result.redirect || 'dashboard.php';
      });
    },
    onError: function (err) {
      showError(err && err.message ? err.message : 'PayPal checkout failed. Please try again.');
    }
  }).render('#paypal-button-container');
})();
</script>
<?php endif; ?>

<?php
require_once('inc/footer.inc.php');
?>
