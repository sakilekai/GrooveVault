<?php
/* GrooveVault — Database connection test page.
   Open in your browser: http://localhost/GrooveVault/db_test.php
   Delete this file once you've confirmed the connection works. */

require_once __DIR__ . '/inc/db.inc.php';   // exits with an error if it can't connect

// If we got here, $pdo connected. Gather a bit of info to display.
$dbName    = $pdo->query('SELECT DATABASE()')->fetchColumn();
$version   = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
$tables    = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GrooveVault — DB Connection Test</title>
<style>
  body{font-family:system-ui,Segoe UI,sans-serif;background:#0D0618;color:#F0E6FF;
       display:flex;justify-content:center;align-items:flex-start;min-height:100vh;padding:3rem 1rem;}
  .card{background:#160D28;border:1px solid #2D1854;border-radius:16px;padding:2rem 2.5rem;max-width:560px;width:100%;}
  h1{font-size:1.4rem;margin:0 0 1.2rem;}
  .ok{color:#39FF14;}
  .warn{color:#FFE600;}
  table{width:100%;border-collapse:collapse;margin-top:.5rem;}
  td{padding:.45rem .6rem;border-bottom:1px solid #2D1854;font-size:.92rem;}
  td:first-child{color:#8A7AAA;width:40%;}
  code{background:#0D0618;padding:.15rem .4rem;border-radius:5px;color:#00D4FF;}
  ul{margin:.4rem 0 0;padding-left:1.2rem;color:#00D4FF;}
  .muted{color:#8A7AAA;font-size:.85rem;margin-top:1.4rem;}
</style>
</head>
<body>
  <div class="card">
    <h1><span class="ok">&#10004; Database connected successfully!</span></h1>
    <table>
      <tr><td>Connected database</td><td><code><?= htmlspecialchars($dbName) ?></code></td></tr>
      <tr><td>Server version</td><td><?= htmlspecialchars($version) ?></td></tr>
      <tr><td>Host</td><td><?= htmlspecialchars(DB_HOST . ':' . DB_PORT) ?></td></tr>
      <tr><td>Tables found</td><td><?= count($tables) ?></td></tr>
    </table>

    <?php if ($tables): ?>
      <ul>
        <?php foreach ($tables as $t): ?>
          <li><?= htmlspecialchars($t) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="warn" style="margin-top:1rem;">&#9888; The database is empty — no tables created yet.</p>
    <?php endif; ?>

    <p class="muted">This is a temporary test page. Delete <code>db_test.php</code> once you've confirmed everything works.</p>
  </div>
</body>
</html>
