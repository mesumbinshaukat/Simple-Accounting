<?php
require __DIR__ . '/_init.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
  exit;
}

$data = json_input();
$username = trim($data['username'] ?? '');
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'missing_fields']);
  exit;
}

// Check if exists
$stmt = db()->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  http_response_code(409);
  echo json_encode(['ok' => false, 'error' => 'username_taken']);
  exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt2 = db()->prepare('INSERT INTO users(username, password_hash) VALUES (?, ?)');
$stmt2->bind_param('ss', $username, $hash);
if ($stmt2->execute()) {
  echo json_encode(['ok' => true, 'id' => (int)db()->insert_id]);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'db_error']);
}
