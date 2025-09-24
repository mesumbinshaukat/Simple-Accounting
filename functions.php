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

function balance_class($amount) {
    if ($amount > 0) return 'text-pos';
    if ($amount < 0) return 'text-neg';
    return 'text-zero';
}

function status_badge($amount) {
    if ($amount > 0) return '<span class="badge pos">To Receive</span>';
    if ($amount < 0) return '<span class="badge neg">To Return</span>';
    return '<span class="badge zero">Settled</span>';
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

function get_total_balance() {
    $sql = "SELECT COALESCE(SUM(CASE WHEN t.type='credit' THEN t.amount WHEN t.type='debit' THEN -t.amount END),0) AS total
            FROM accounts a
            LEFT JOIN transactions t ON t.account_id = a.id";
    $res = db()->query($sql);
    if ($res && ($row = $res->fetch_assoc())) {
        return (float)$row['total'];
    }
    return 0.0;
}

function get_open_totals_cached() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $sql = "SELECT
              COALESCE(SUM(CASE WHEN x.bal > 0 THEN x.bal ELSE 0 END),0) AS total_credit,
              COALESCE(SUM(CASE WHEN x.bal < 0 THEN -x.bal ELSE 0 END),0) AS total_debit
            FROM (
                SELECT a.id,
                       COALESCE(SUM(CASE WHEN t.type='credit' THEN t.amount WHEN t.type='debit' THEN -t.amount END),0) AS bal
                FROM accounts a
                LEFT JOIN transactions t ON t.account_id = a.id
                GROUP BY a.id
            ) x
            WHERE x.bal <> 0";
    $res = db()->query($sql);
    $totCred = 0.0; $totDeb = 0.0;
    if ($res && ($row = $res->fetch_assoc())) {
        $totCred = (float)$row['total_credit'];
        $totDeb = (float)$row['total_debit'];
    }
    $cache = [$totCred, $totDeb];
    return $cache;
}

function get_total_credit() {
    [$totCred, $totDeb] = get_open_totals_cached();
    return $totCred;
}

function get_total_debit() {
    [$totCred, $totDeb] = get_open_totals_cached();
    return $totDeb;
}
