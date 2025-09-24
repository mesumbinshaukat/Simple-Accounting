<?php
require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';

// Log out user
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

add_flash('You have been logged out.', 'info');
redirect('/simple_accounting/login.php');
