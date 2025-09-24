<?php
require __DIR__ . '/_init.php';
api_require_auth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $sql = "SELECT a.id, a.name, a.created_at,
                   COALESCE(SUM(CASE WHEN t.type='credit' THEN t.amount WHEN t.type='debit' THEN -t.amount END),0) AS balance
            FROM accounts a
            LEFT JOIN transactions t ON t.account_id = a.id
            GROUP BY a.id, a.name, a.created_at
            ORDER BY a.created_at DESC";
    $res = db()->query($sql);
    $rows = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'created_at' => $r['created_at'],
                'balance' => (float)$r['balance'],
            ];
        }
    }
    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

if ($method === 'POST') {
    $data = json_input();
    $name = trim($data['name'] ?? '');
    if ($name === '' || strlen($name) > 100) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_name']);
        exit;
    }
    $stmt = db()->prepare('INSERT INTO accounts(name) VALUES (?)');
    $stmt->bind_param('s', $name);
    if ($stmt->execute()) {
        echo json_encode(['ok' => true, 'id' => (int)db()->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db_error']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
