<?php
require_once __DIR__ . '/../inc/functions.inc.php';
// Log out the admin but leave any end-user session intact.
unset($_SESSION['admin_id']);
redirect('login.php');
