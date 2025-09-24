<?php
require __DIR__ . '/_init.php';
api_require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
  exit;
}

$payload = json_input();
$results = [];
$actions = is_array($payload['actions'] ?? null) ? $payload['actions'] : [];

foreach ($actions as $i => $act) {
  $type = $act['type'] ?? '';
  try {
    if ($type === 'create_account') {
      $name = trim($act['name'] ?? '');
      if ($name === '' || strlen($name) > 100) throw new Exception('invalid_name');
      $stmt = db()->prepare('INSERT INTO accounts(name) VALUES (?)');
      $stmt->bind_param('s', $name);
      if (!$stmt->execute()) throw new Exception('db_error');
      $results[] = ['index' => $i, 'ok' => true, 'id' => (int)db()->insert_id];
    } elseif ($type === 'transaction') {
      $accountId = (int)($act['account_id'] ?? 0);
      $action = $act['action'] ?? '';
      $amount = (float)($act['amount'] ?? 0);
      $note = trim((string)($act['note'] ?? ''));
      $transferTo = (int)($act['transfer_to'] ?? 0);
      if (!in_array($action, ['credit','debit'], true) || $accountId <= 0 || $amount <= 0) throw new Exception('invalid_tx');
      if (strlen($note) > 255) $note = substr($note, 0, 255);
      $stmt = db()->prepare('INSERT INTO transactions(account_id, type, amount, note) VALUES (?,?,?,?)');
      $stmt->bind_param('isds', $accountId, $action, $amount, $note);
      if (!$stmt->execute()) throw new Exception('db_error');
      if ($action === 'debit' && $transferTo > 0 && $transferTo !== $accountId) {
        $target = find_account($transferTo);
        if ($target) {
          $note2 = $note !== '' ? $note : ('Transfer from account #' . $accountId);
          $stmt2 = db()->prepare('INSERT INTO transactions(account_id, type, amount, note) VALUES (?,?,?,?)');
          $type2 = 'credit';
          $stmt2->bind_param('isds', $transferTo, $type2, $amount, $note2);
          $stmt2->execute();
        }
      }
      $results[] = ['index' => $i, 'ok' => true];
    } else {
      $results[] = ['index' => $i, 'ok' => false, 'error' => 'unknown_type'];
    }
  } catch (Exception $e) {
    $results[] = ['index' => $i, 'ok' => false, 'error' => $e->getMessage()];
  }
}

echo json_encode(['ok' => true, 'results' => $results]);
