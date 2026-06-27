<?php
require_once __DIR__ . '/inc/paypal.inc.php';
require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

if (!paypal_configured()) {
    json_response(['error' => 'PayPal is not configured. Ask the admin to add credentials in Settings.'], 503);
}

$input  = verify_csrf_json();
$planId = (int)($input['plan_id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM plans WHERE id = ?');
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    json_response(['error' => 'Invalid plan selected.'], 422);
}

// Block downgrading to a plan that can't hold the user's current channels.
$block = plan_downgrade_block((int)$user['id'], $plan);
if ($block !== null) {
    json_response(['error' => $block], 422);
}

$result = paypal_create_order($plan);
if (!$result['ok']) {
    json_response(['error' => $result['error']], 502);
}

json_response(['orderID' => $result['order_id']]);
