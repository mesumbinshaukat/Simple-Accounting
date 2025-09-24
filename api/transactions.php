<?php
require __DIR__ . '/_init.php';
api_require_auth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $accountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
    if ($accountId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'missing_account_id']);
        exit;
    }
    $stmt = db()->prepare("SELECT id, type, amount, note, created_at FROM transactions WHERE account_id=? ORDER BY created_at DESC, id DESC");
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'id' => (int)$r['id'],
            'type' => $r['type'],
            'amount' => (float)$r['amount'],
            'note' => $r['note'],
            'created_at' => $r['created_at'],
        ];
    }
    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

if ($method === 'POST') {
    $data = json_input();
    $accountId = (int)($data['account_id'] ?? 0);
    $action = $data['action'] ?? '';
    $amount = (float)($data['amount'] ?? 0);
    $note = trim((string)($data['note'] ?? ''));
    $transferTo = (int)($data['transfer_to'] ?? 0);

    if (!in_array($action, ['credit','debit'], true) || $accountId <= 0 || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_fields']);
        exit;
    }
    if (strlen($note) > 255) $note = substr($note, 0, 255);

    $stmt = db()->prepare("INSERT INTO transactions(account_id, type, amount, note) VALUES (?,?,?,?)");
    $stmt->bind_param('isds', $accountId, $action, $amount, $note);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db_error']);
        exit;
    }

    // If debit with transfer_to, also credit the target
    if ($action === 'debit' && $transferTo > 0 && $transferTo !== $accountId) {
        $target = find_account($transferTo);
        if ($target) {
            $note2 = $note !== '' ? $note : ('Transfer from account #' . $accountId);
            $stmt2 = db()->prepare("INSERT INTO transactions(account_id, type, amount, note) VALUES (?,?,?,?)");
            $type2 = 'credit';
            $stmt2->bind_param('isds', $transferTo, $type2, $amount, $note2);
            $stmt2->execute();
        }
    }

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
