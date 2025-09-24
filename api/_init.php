<?php
require __DIR__ . '/../db.php';
require __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

function api_require_auth() {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'unauthorized']);
        exit;
    }
}

function json_input() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
