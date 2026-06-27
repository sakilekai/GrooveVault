<?php
/* Shared ADMIN-side header (used by pages inside /admin/).
   Set these BEFORE including this file (all optional):
     $pageTitle   – browser tab title
     $adminActive – which sidebar item is active:
                    dashboard | users | channels | subscriptions | activity | suspended | settings
     $topbarTitle – heading shown in the top bar
     $topbarRight – raw HTML for the right side of the top bar (defaults to the avatar)
     $adminBare   – true to skip the sidebar/topbar/main shell (for login & modal screens) */
require_once __DIR__ . '/functions.inc.php';
$pageTitle   = $pageTitle   ?? 'GrooveVault Admin';
$adminActive = $adminActive ?? '';
$topbarTitle = $topbarTitle ?? '';
$topbarRight = $topbarRight ?? '<div class="admin-avatar">A</div>';
$adminBare   = $adminBare   ?? false;

if (!function_exists('gv_admin_active')) {
    function gv_admin_active($key, $current) { return $key === $current ? ' active' : ''; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="icon" type="image/png" href="../assets/favicon.png">
<link rel="apple-touch-icon" href="../assets/favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../gv-admin.css" rel="stylesheet">
<style>html,body{min-height:100%;}</style>
</head>
<body>
<div class="view" style="min-height:100vh;">
<?php if (!$adminBare): ?>
  <div class="sidebar">
    <div class="sidebar-logo">
      <a href="dashboard.php" class="text-decoration-none"><div class="logo-text">GrooveVault <span class="logo-badge">ADMIN</span></div></a>
    </div>
    <div style="flex:1;padding:.5rem 0;">
      <div class="sidebar-section">Main</div>
      <a href="dashboard.php" class="nav-item-admin<?= gv_admin_active('dashboard', $adminActive) ?>"><i class="bi bi-grid-1x2 nav-icon"></i> Dashboard</a>
      <a href="users.php" class="nav-item-admin<?= gv_admin_active('users', $adminActive) ?>"><i class="bi bi-people nav-icon"></i> Users <span class="nav-badge"><?= (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() ?></span></a>
      <a href="channels.php" class="nav-item-admin<?= gv_admin_active('channels', $adminActive) ?>"><i class="bi bi-collection-play nav-icon"></i> Channels</a>
      <a href="subscriptions.php" class="nav-item-admin<?= gv_admin_active('subscriptions', $adminActive) ?>"><i class="bi bi-credit-card nav-icon"></i> Subscriptions</a>
      <a href="plans.php" class="nav-item-admin<?= gv_admin_active('plans', $adminActive) ?>"><i class="bi bi-tags nav-icon"></i> Plans</a>
      <div class="sidebar-section">Moderation</div>
      <a href="activity_log.php" class="nav-item-admin<?= gv_admin_active('activity', $adminActive) ?>"><i class="bi bi-activity nav-icon"></i> Activity Log</a>
      <a href="suspended_users.php" class="nav-item-admin<?= gv_admin_active('suspended', $adminActive) ?>"><i class="bi bi-slash-circle nav-icon"></i> Suspended</a>
      <div class="sidebar-section">Settings</div>
      <a href="settings.php" class="nav-item-admin<?= gv_admin_active('settings', $adminActive) ?>"><i class="bi bi-gear nav-icon"></i> Admin Settings</a>
    </div>
    <div class="sidebar-footer">
      <a href="logout.php" class="nav-item-admin" style="margin:0;"><i class="bi bi-box-arrow-right nav-icon"></i> Log Out</a>
    </div>
  </div>
  <div class="topbar">
    <div class="d-flex align-items-center" style="min-width:0;">
      <button type="button" class="sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
      <div class="topbar-title text-truncate"><?= htmlspecialchars($topbarTitle) ?></div>
    </div>
    <div class="d-flex align-items-center gap-3"><?= $topbarRight ?></div>
  </div>
  <div class="sidebar-backdrop" id="adminSidebarBackdrop"></div>
  <script>
  (function () {
    var v = document.querySelector('.view'),
        t = document.getElementById('adminSidebarToggle'),
        b = document.getElementById('adminSidebarBackdrop');
    if (!v || !t) return;
    function close() { v.classList.remove('sidebar-open'); }
    t.addEventListener('click', function (e) { e.stopPropagation(); v.classList.toggle('sidebar-open'); });
    if (b) b.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  })();
  </script>
  <div class="main">
    <?= gv_admin_flash() ?>
<?php endif; ?>
