<?php require_once __DIR__ . '/../db.php'; require_once __DIR__ . '/../functions.php'; require_once __DIR__ . '/../auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e(app_name()); ?></title>
  <link rel="stylesheet" href="/simple_accounting/assets/style.css" />
</head>
<body>
  <div class="app">
    <aside class="sidebar" id="sidebar">
      <div class="brand"><?= e(app_name()); ?></div>
      <nav>
        <a href="/simple_accounting/index.php" class="nav-item">Dashboard</a>
        <a href="/simple_accounting/account_new.php" class="nav-item">New Account</a>
        <a href="/simple_accounting/logout.php" class="nav-item">Logout</a>
      </nav>
    </aside>
    <div class="content">
      <header class="topbar">
        <button class="menu-btn" id="menuToggle">☰</button>
        <h1 class="page-title"><?= e(app_name()); ?></h1>
      </header>
      <main class="main">
