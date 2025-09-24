<?php
require __DIR__ . '/_init.php';
if (empty($_SESSION['user_id'])) {
  echo json_encode(['ok' => true, 'user' => null]);
  exit;
}
echo json_encode(['ok' => true, 'user' => ['id' => (int)$_SESSION['user_id'], 'username' => $_SESSION['username'] ?? '']]);
