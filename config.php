<?php
// Configuration loader with .env support (no external libs)

function load_env($path)
{
    $vars = [];
    if (!is_file($path)) {
        return $vars;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // skip comments and empty
        if ($line === '' || substr($line, 0, 1) === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        // strip surrounding quotes if any
        $len = strlen($value);
        if ($len >= 2) {
            $first = substr($value, 0, 1);
            $last = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        $vars[$key] = $value;
    }
    return $vars;
}

$env = load_env(__DIR__ . '/.env');

return [
    'db_host' => $env['DB_HOST'] ?? 'localhost',
    'db_user' => $env['DB_USER'] ?? 'root',
    'db_pass' => $env['DB_PASS'] ?? '', // Default XAMPP MySQL has empty password
    'db_name' => $env['DB_NAME'] ?? 'simple_accounting',
    'app_name' => $env['APP_NAME'] ?? 'Simple Accounting',
    // optional admin user bootstrap
    'admin_user' => $env['ADMIN_USER'] ?? null,
    'admin_pass' => $env['ADMIN_PASS'] ?? null,
];
