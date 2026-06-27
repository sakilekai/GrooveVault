<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_admin();
$pageTitle = 'GrooveVault — Confirm';
$adminBare = true;
require_once __DIR__ . '/../inc/admin-header.inc.php';
?>

  <!-- Reusable confirmation style. Destructive actions across the panel use inline
       confirm() dialogs on their own forms; this page documents the modal pattern. -->

  <div class="modal-stage">
    <div class="admin-modal-card text-center">
      <div class="activity-dot" style="width:48px;height:48px;margin:0 auto 1rem;background:rgba(245,69,107,0.12);color:var(--accent-pink);font-size:1.3rem;"><i class="bi bi-exclamation-triangle"></i></div>
      <h5 style="font-family:'Bebas Neue',sans-serif;letter-spacing:.5px;color:var(--accent-pink);">ARE YOU SURE?</h5>
      <p style="color:var(--text-dim);font-size:.85rem;margin:.8rem 0 1.4rem;">This deletes the user and all their channels. This action cannot be undone.</p>
      <div class="d-flex justify-content-center gap-2">
        <a href="users.php" class="btn-admin btn-admin-ghost text-decoration-none">Cancel</a>
        <a href="users.php" class="btn-admin btn-admin-danger text-decoration-none">Confirm Delete</a>
      </div>
    </div>
  </div>

<?php require_once __DIR__ . '/../inc/admin-footer.inc.php'; ?>
