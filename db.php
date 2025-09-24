<?php
// Database connection and simple auto-migrations
if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie params and persist for ~6 months (180 days)
    $sixMonths = 180 * 24 * 60 * 60; // 15552000 seconds
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_lifetime', (string)$sixMonths);
    ini_set('session.gc_maxlifetime', (string)$sixMonths);
    // If you enable HTTPS later, also set cookie_secure to 1
    // ini_set('session.cookie_secure', '1');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => $sixMonths,
            'path' => '/',
            'domain' => '',
            'secure' => false, // set true when serving over HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

$config = require __DIR__ . '/config.php';

$host = $config['db_host'];
$user = $config['db_user'];
$pass = $config['db_pass'];
$dbname = $config['db_name'];

$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

// Create database if not exists
$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$mysqli->select_db($dbname);

// Create tables if not exist
$createAccounts = "CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$createTransactions = "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    type ENUM('debit','credit') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$mysqli->query($createAccounts)) {
    die('Failed creating accounts table: ' . $mysqli->error);
}
if (!$mysqli->query($createTransactions)) {
    die('Failed creating transactions table: ' . $mysqli->error);
}

// Users table for single-user login
$createUsers = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$mysqli->query($createUsers)) {
    die('Failed creating users table: ' . $mysqli->error);
}

// Seed single admin if provided via .env and no user exists
$res = $mysqli->query("SELECT COUNT(*) AS cnt FROM users");
$row = $res ? $res->fetch_assoc() : ['cnt' => 0];
if ((int)$row['cnt'] === 0) {
    $adminUser = $config['admin_user'] ?? null;
    $adminPass = $config['admin_pass'] ?? null;
    if ($adminUser && $adminPass) {
        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users(username, password_hash) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param('ss', $adminUser, $hash);
            $stmt->execute();
        }
    }
}

function db() {
    global $mysqli;
    return $mysqli;
}
