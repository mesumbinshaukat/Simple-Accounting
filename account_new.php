<?php require __DIR__ . '/layout/header.php'; ?>
<?php
$name = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        add_flash('Account name is required.', 'error');
    } else {
        // basic validation: limit length and characters
        if (strlen($name) > 100) {
            add_flash('Account name is too long (max 100).', 'error');
        } else {
        $stmt = db()->prepare("INSERT INTO accounts(name) VALUES (?)");
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            $newId = db()->insert_id;
            add_flash('Account created successfully.', 'success');
            redirect('/simple_accounting/account.php?id=' . $newId);
        } else {
            add_flash('Failed to create account: ' . db()->error, 'error');
        }
        }
    }
}
?>
<div class="card" style="max-width:520px;">
  <h2 style="margin-top:0">New Account</h2>
  <form method="post">
    <?php csrf_field(); ?>
    <label>Account Name</label>
    <input class="input" type="text" name="name" value="<?= e($name); ?>" placeholder="e.g., Cash, Savings, Wallet" required />
    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Create</button>
      <a class="btn secondary" href="/simple_accounting/index.php" type="button">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
