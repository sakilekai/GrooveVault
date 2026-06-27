<?php
/* GrooveVault — PayPal Checkout (Orders API v2).
   Credentials are stored in the settings table (admin → Settings → PayPal). */

require_once __DIR__ . '/functions.inc.php';

function paypal_client_id(): string {
    return setting('paypal_client_id', '');
}

function paypal_client_secret(): string {
    return setting('paypal_client_secret', '');
}

function paypal_mode(): string {
    return setting('paypal_mode', 'sandbox') === 'live' ? 'live' : 'sandbox';
}

function paypal_configured(): bool {
    return paypal_client_id() !== '' && paypal_client_secret() !== '';
}

function paypal_api_base(): string {
    return paypal_mode() === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';
}

function paypal_sdk_url(): string {
    $clientId = urlencode(paypal_client_id());
    return 'https://www.paypal.com/sdk/js?client-id=' . $clientId . '&currency=USD&intent=capture';
}

/** @return array{ok: bool, token: ?string, error: ?string} */
function paypal_access_token(): array {
    if (!paypal_configured()) {
        return ['ok' => false, 'token' => null, 'error' => 'PayPal is not configured.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'token' => null, 'error' => 'PHP cURL extension is not enabled.'];
    }

    $ch = curl_init(paypal_api_base() . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERPWD        => paypal_client_id() . ':' . paypal_client_secret(),
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Accept-Language: en_US'],
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cErr = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'token' => null, 'error' => 'Network error: ' . $cErr];
    }

    $data = json_decode($resp, true);
    if ($code >= 200 && $code < 300 && !empty($data['access_token'])) {
        return ['ok' => true, 'token' => $data['access_token'], 'error' => null];
    }

    $msg = is_array($data) && !empty($data['error_description'])
        ? $data['error_description']
        : ('PayPal auth failed (HTTP ' . $code . ')');
    return ['ok' => false, 'token' => null, 'error' => $msg];
}

/** @return array{ok: bool, order_id: ?string, error: ?string} */
function paypal_create_order(array $plan): array {
    $auth = paypal_access_token();
    if (!$auth['ok']) {
        return ['ok' => false, 'order_id' => null, 'error' => $auth['error']];
    }

    $amount = number_format((float)$plan['price'], 2, '.', '');
    $per    = $plan['billing_period'] === 'annual' ? 'year' : 'month';
    $payload = [
        'intent'         => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'plan_' . $plan['id'],
            'description'  => $plan['name'] . ' (' . $per . 'ly)',
            'custom_id'    => (string)$plan['id'],
            'amount'       => [
                'currency_code' => 'USD',
                'value'         => $amount,
            ],
        ]],
    ];

    $ch = curl_init(paypal_api_base() . '/v2/checkout/orders');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $auth['token'],
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cErr = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'order_id' => null, 'error' => 'Network error: ' . $cErr];
    }

    $data = json_decode($resp, true);
    if ($code >= 200 && $code < 300 && !empty($data['id'])) {
        return ['ok' => true, 'order_id' => $data['id'], 'error' => null];
    }

    $msg = is_array($data) && !empty($data['message']) ? $data['message'] : ('PayPal order failed (HTTP ' . $code . ')');
    return ['ok' => false, 'order_id' => null, 'error' => $msg];
}

/** @return array{ok: bool, capture_id: ?string, amount: ?float, plan_id: ?int, error: ?string} */
function paypal_capture_order(string $orderId): array {
    $auth = paypal_access_token();
    if (!$auth['ok']) {
        return ['ok' => false, 'capture_id' => null, 'amount' => null, 'plan_id' => null, 'error' => $auth['error']];
    }

    $ch = curl_init(paypal_api_base() . '/v2/checkout/orders/' . urlencode($orderId) . '/capture');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $auth['token'],
        ],
        CURLOPT_POSTFIELDS     => '{}',
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cErr = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'capture_id' => null, 'amount' => null, 'plan_id' => null, 'error' => 'Network error: ' . $cErr];
    }

    $data = json_decode($resp, true);
    if ($code < 200 || $code >= 300) {
        $msg = is_array($data) && !empty($data['message']) ? $data['message'] : ('PayPal capture failed (HTTP ' . $code . ')');
        return ['ok' => false, 'capture_id' => null, 'amount' => null, 'plan_id' => null, 'error' => $msg];
    }

    $capture   = $data['purchase_units'][0]['payments']['captures'][0] ?? null;
    $captureId = $capture['id'] ?? $orderId;
    $amount    = isset($capture['amount']['value']) ? (float)$capture['amount']['value'] : null;
    $planId    = null;
    if (!empty($data['purchase_units'][0]['payments']['captures'][0]['custom_id'])) {
        $planId = (int)$data['purchase_units'][0]['payments']['captures'][0]['custom_id'];
    } elseif (!empty($data['purchase_units'][0]['custom_id'])) {
        $planId = (int)$data['purchase_units'][0]['custom_id'];
    }

    return ['ok' => true, 'capture_id' => $captureId, 'amount' => $amount, 'plan_id' => $planId, 'error' => null];
}

/** Activate plan after verified PayPal payment. */
function activate_user_plan(int $userId, array $plan, string $paypalTxnId): void {
    db()->prepare('UPDATE subscriptions SET status = "cancelled" WHERE user_id = ? AND status = "active"')
        ->execute([$userId]);

    $mrr = $plan['billing_period'] === 'annual'
         ? round((float)$plan['price'] / 12, 2)
         : (float)$plan['price'];

    // Term length: annual plans run a year, everything else a month.
    $interval = $plan['billing_period'] === 'annual' ? 'INTERVAL 1 YEAR' : 'INTERVAL 1 MONTH';

    db()->prepare('UPDATE users SET plan_id = ? WHERE id = ?')->execute([(int)$plan['id'], $userId]);
    db()->prepare(
        "INSERT INTO subscriptions (user_id, plan_id, status, amount_paid, mrr, billing_period, paypal_txn, started_at, expires_at)
         VALUES (?, ?, 'active', ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), $interval))"
    )->execute([
        $userId,
        (int)$plan['id'],
        (float)$plan['price'],
        $mrr,
        $plan['billing_period'],
        $paypalTxnId,
    ]);
}

/** Verify CSRF token from JSON request body. */
function verify_csrf_json(): array {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = [];
    }
    $sent = $input['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Session expired. Refresh the page and try again.']);
        exit;
    }
    return $input;
}

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
