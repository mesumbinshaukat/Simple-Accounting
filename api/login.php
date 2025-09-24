<?php
require __DIR__ . '/_init.php';

$data = json_input();
$username = trim($data['username'] ?? '');
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'missing_fields']);
  exit;
}

$stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($uid, $uname, $phash);
if ($stmt->num_rows === 1 && $stmt->fetch()) {
  if (password_verify($password, $phash)) {
    $_SESSION['user_id'] = (int)$uid;
    $_SESSION['username'] = $uname;
    session_regenerate_id(true);
    echo json_encode(['ok' => true, 'user' => ['id' => (int)$uid, 'username' => $uname]]);
    exit;
  }
}
http_response_code(401);
echo json_encode(['ok' => false, 'error' => 'invalid_credentials']);
