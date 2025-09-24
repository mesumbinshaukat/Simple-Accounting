<?php
// One-time migration trigger: include db.php to run auto-migrations
require __DIR__ . '/db.php';
header('Content-Type: text/plain; charset=utf-8');

// Verify tables exist
$checks = [
  'accounts' => false,
  'transactions' => false,
  'users' => false,
];

// Determine current schema (DB name) without re-including config.php
// Prefer $config from db.php if available, otherwise ask MySQL
$schema = isset($config['db_name']) ? $config['db_name'] : null;
if ($schema === null) {
    $resDb = db()->query('SELECT DATABASE() AS db');
    $rowDb = $resDb ? $resDb->fetch_assoc() : null;
    $schema = $rowDb ? $rowDb['db'] : '';
}

// Use INFORMATION_SCHEMA with a prepared statement
foreach (array_keys($checks) as $t) {
    $sql = 'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->bind_param('ss', $schema, $t);
    $stmt->execute();
    $res = $stmt->get_result();
    $checks[$t] = ($res && $res->num_rows > 0);
}

echo "Database: {$schema}\n";
foreach ($checks as $name => $exists) {
    echo ($exists ? '[OK]   ' : '[MISS] ') . $name . "\n";
}

$allOk = array_reduce($checks, function($c, $v){ return $c && $v; }, true);

echo "\n" . ($allOk ? 'All tables are present.' : 'Some tables are missing. Reload this page or check credentials in .env.') . "\n";
