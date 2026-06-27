<?php
require_once __DIR__ . '/inc/functions.inc.php';
// Log out the end user but keep any admin session intact.
unset($_SESSION['user_id'], $_SESSION['pending_verification_user']);
flash_set('info', 'You have been logged out.');
redirect('login.php');
