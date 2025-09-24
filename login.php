<?php
require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';

if (!empty($_SESSION['user_id'])) {
  redirect('/simple_accounting/index.php');
}

$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    add_flash('Username and password are required.', 'error');
  } else {
    $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
    if ($stmt) {
      $stmt->bind_param('s', $username);
      $stmt->execute();
      // Use bind_result to avoid dependency on mysqlnd get_result
      $stmt->store_result();
      $stmt->bind_result($uid, $uname, $phash);
      if ($stmt->num_rows === 1 && $stmt->fetch()) {
        if (password_verify($password, $phash)) {
          $_SESSION['user_id'] = (int)$uid;
          $_SESSION['username'] = $uname;
          session_regenerate_id(true);
          add_flash('Welcome back, ' . $uname . '!', 'success');
          redirect('/simple_accounting/index.php');
        }
      }
      add_flash('Invalid credentials.', 'error');
    } else {
      add_flash('Login failed. Please try again.', 'error');
    }
  }
}
$flashes = pop_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?= e(app_name()); ?></title>
  <link rel="stylesheet" href="/simple_accounting/assets/style.css" />
  <style>
    .center {min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
    .login-card{width:100%;max-width:420px}
  </style>
</head>
<body>
  <div class="center">
    <div class="card login-card">
      <h2 style="margin-top:0">Login</h2>
      <form method="post" autocomplete="off">
        <?php csrf_field(); ?>
        <label>Username</label>
        <input class="input" type="text" name="username" value="<?= e($username); ?>" required>
        <label style="margin-top:8px;">Password</label>
        <input class="input" type="password" name="password" required>
        <button class="btn" type="submit" style="margin-top:12px;">Sign In</button>
      </form>
    </div>
  </div>
  <div id="toast-container"></div>
  <script src="/simple_accounting/assets/app.js"></script>
  <?php if (!empty($flashes)): ?>
  <script>
    (function(){
      <?php foreach ($flashes as $f): ?>
      addToast(<?= json_encode($f['message']); ?>, <?= json_encode($f['type']); ?>);
      <?php endforeach; ?>
    })();
  </script>
  <?php endif; ?>
</body>
</html>
