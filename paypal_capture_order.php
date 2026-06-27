<?php
require_once __DIR__ . '/inc/paypal.inc.php';
require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed.'], 405);
}

if (!paypal_configured()) {
    json_response(['error' => 'PayPal is not configured.'], 503);
}

$input   = verify_csrf_json();
$orderId = trim($input['orderID'] ?? '');
$planId  = (int)($input['plan_id'] ?? 0);

if ($orderId === '') {
    json_response(['error' => 'Missing PayPal order ID.'], 422);
}

$stmt = db()->prepare('SELECT * FROM plans WHERE id = ?');
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    json_response(['error' => 'Invalid plan selected.'], 422);
}

// Safety net: never charge if the user is over the target plan's channel limit.
$block = plan_downgrade_block((int)$user['id'], $plan);
if ($block !== null) {
    json_response(['error' => $block], 422);
}

$result = paypal_capture_order($orderId);
if (!$result['ok']) {
    json_response(['error' => $result['error']], 502);
}

// Ensure captured amount matches the plan price from admin panel.
$expected = round((float)$plan['price'], 2);
$paid     = round((float)($result['amount'] ?? 0), 2);
if ($paid > 0 && abs($paid - $expected) > 0.01) {
    json_response(['error' => 'Payment amount does not match the selected plan price.'], 422);
}

$txnId = $result['capture_id'] ?? $orderId;
activate_user_plan((int)$user['id'], $plan, $txnId);

flash_set('success', 'Welcome to ' . $plan['name'] . '! Payment received via PayPal.');
json_response(['redirect' => 'dashboard.php']);
