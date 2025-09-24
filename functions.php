<?php

function app_name() {
    // Avoid re-including config to prevent function redeclare; use existing global $config set by db.php
    static $cached;
    if ($cached !== null) return $cached;
    if (isset($GLOBALS['config']) && isset($GLOBALS['config']['app_name'])) {
        $cached = $GLOBALS['config']['app_name'];
    } else {
        // Fallback: load once safely
        $cfg = require __DIR__ . '/config.php';
        $cached = $cfg['app_name'] ?? 'App';
    }
    return $cached;
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// CSRF protection helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $t = csrf_token();
    echo '<input type="hidden" name="_token" value="' . e($t) . '">';
}

function verify_csrf() {
    $token = $_POST['_token'] ?? '';
    $valid = !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    if (!$valid) {
        add_flash('Invalid CSRF token. Please try again.', 'error');
        redirect('/simple_accounting/index.php');
    }
}

function add_flash($message, $type = 'success') {
    if (!isset($_SESSION['flashes'])) {
        $_SESSION['flashes'] = [];
    }
    $type = in_array($type, ['success','error','info']) ? $type : 'info';
    $_SESSION['flashes'][] = ['message' => $message, 'type' => $type];
}

function pop_flashes() {
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $flashes;
}

function get_account_balance($account_id) {
    $sql = "SELECT COALESCE(SUM(CASE WHEN type='credit' THEN amount WHEN type='debit' THEN -amount END),0) AS balance FROM transactions WHERE account_id=?";
    $stmt = db()->prepare($sql);
    $stmt->bind_param('i', $account_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return (float)$res['balance'];
}

function find_account($id) {
    $stmt = db()->prepare("SELECT * FROM accounts WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
