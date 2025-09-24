<?php
// Simple auth guard: require user to be logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /simple_accounting/login.php');
    exit;
}
