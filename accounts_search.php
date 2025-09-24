<?php
require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$exclude = isset($_GET['exclude']) ? (int)$_GET['exclude'] : 0;

$sql = "SELECT id, name FROM accounts WHERE (? = '' OR name LIKE ?) AND (? = 0 OR id <> ?) ORDER BY name ASC LIMIT 10";
$stmt = db()->prepare($sql);
if (!$stmt) {
  echo json_encode([]);
  exit;
}
$like = '%' . $q . '%';
$stmt->bind_param('ssii', $q, $like, $exclude, $exclude);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($row = $res->fetch_assoc()) {
  $out[] = [
    'id' => (int)$row['id'],
    'name' => $row['name'],
  ];
}
echo json_encode($out);
