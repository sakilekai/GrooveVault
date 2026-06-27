<?php
require_once __DIR__ . '/../inc/functions.inc.php';
require_once __DIR__ . '/../inc/media_scan.inc.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['safe' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_login();

$sent = $_POST['csrf'] ?? '';
if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
    http_response_code(419);
    echo json_encode(['safe' => false, 'message' => 'Session expired. Refresh the page and try again.']);
    exit;
}

$type = ($_POST['source_type'] ?? 'link') === 'upload' ? 'upload' : 'link';

if ($type === 'upload') {
    if (empty($_FILES['file']['name']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        echo json_encode(['safe' => false, 'message' => 'No file selected for scanning.']);
        exit;
    }
    $result = media_scan_file($_FILES['file']['tmp_name'], $_FILES['file']['name']);
} else {
    $url = trim($_POST['source_url'] ?? '');
    $result = media_scan_url($url);
}

echo json_encode([
    'safe'    => (bool)$result['safe'],
    'message' => $result['message'],
]);
