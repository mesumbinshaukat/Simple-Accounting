<?php
require __DIR__ . '/_init.php';
api_require_auth();

// Return all data needed by client for offline cache
$accounts = [];
$resA = db()->query("SELECT id, name, created_at FROM accounts ORDER BY id ASC");
if ($resA) {
  while ($r = $resA->fetch_assoc()) {
    $accounts[] = [
      'id' => (int)$r['id'],
      'name' => $r['name'],
      'created_at' => $r['created_at'],
    ];
  }
}
$transactions = [];
$resT = db()->query("SELECT id, account_id, type, amount, note, created_at FROM transactions ORDER BY id ASC");
if ($resT) {
  while ($r = $resT->fetch_assoc()) {
    $transactions[] = [
      'id' => (int)$r['id'],
      'account_id' => (int)$r['account_id'],
      'type' => $r['type'],
      'amount' => (float)$r['amount'],
      'note' => $r['note'],
      'created_at' => $r['created_at'],
    ];
  }
}

echo json_encode(['ok' => true, 'data' => [
  'accounts' => $accounts,
  'transactions' => $transactions,
]]);
