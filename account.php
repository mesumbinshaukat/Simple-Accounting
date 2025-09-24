<?php require __DIR__ . '/layout/header.php'; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$account = $id ? find_account($id) : null;
if (!$account) {
  add_flash('Account not found.', 'error');
  redirect('/simple_accounting/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $action = $_POST['action'] ?? '';
  // whitelist action
  if (!in_array($action, ['credit','debit'], true)) {
    add_flash('Invalid action.', 'error');
    redirect('/simple_accounting/account.php?id=' . $id);
  }
  $amountRaw = $_POST['amount'] ?? '';
  $amount = is_numeric($amountRaw) ? (float)$amountRaw : 0.0;
  $note = trim((string)($_POST['note'] ?? ''));
  if ($amount <= 0) {
    add_flash('Amount must be greater than zero.', 'error');
    redirect('/simple_accounting/account.php?id=' . $id);
  }
  // limit note length
  if (strlen($note) > 255) {
    $note = substr($note, 0, 255);
  }
  if ($action === 'credit' || $action === 'debit') {
    $stmt = db()->prepare("INSERT INTO transactions(account_id, type, amount, note) VALUES (?,?,?,?)");
    $stmt->bind_param('isds', $id, $action, $amount, $note);
    if ($stmt->execute()) {
      add_flash(ucfirst($action) . ' successful.', 'success');
    } else {
      add_flash('Transaction failed: ' . db()->error, 'error');
    }
  }
  redirect('/simple_accounting/account.php?id=' . $id);
}

$balance = get_account_balance($id);
$tx = db()->prepare("SELECT id, type, amount, note, created_at FROM transactions WHERE account_id=? ORDER BY created_at DESC, id DESC");
$tx->bind_param('i', $id);
$tx->execute();
$transactions = $tx->get_result();
?>
<div class="grid">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
      <h2 style="margin:0;">Account: <?= e($account['name']); ?></h2>
      <a class="btn secondary" href="/simple_accounting/index.php">Back</a>
    </div>
    <p style="margin:6px 0 0 0;">Current Balance: <span class="balance"><?= number_format($balance, 2); ?></span></p>
  </div>

  <div class="grid grid-2">
    <div class="card">
      <h3 style="margin-top:0;">Credit</h3>
      <form method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="credit">
        <label>Amount</label>
        <input class="input" type="number" name="amount" step="0.01" min="0.01" required>
        <label style="margin-top:8px;">Note (optional)</label>
        <input class="input" type="text" name="note" placeholder="e.g., Deposit">
        <button class="btn" type="submit" style="margin-top:10px;">Meine Diya</button>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0;">Debit</h3>
      <form method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="debit">
        <label>Amount</label>
        <input class="input" type="number" name="amount" step="0.01" min="0.01" required>
        <label style="margin-top:8px;">Note (optional)</label>
        <input class="input" type="text" name="note" placeholder="e.g., Purchase">
        <button class="btn secondary" type="submit" style="margin-top:10px;">Meine Liya</button>
      </form>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0;">Transactions</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Note</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($transactions && $transactions->num_rows > 0): ?>
        <?php while($row = $transactions->fetch_assoc()): ?>
          <tr>
            <td><?= e($row['created_at']); ?></td>
            <td><span class="badge"><?= e(ucfirst($row['type'])); ?></span></td>
            <td class="balance"><?= number_format((float)$row['amount'], 2); ?></td>
            <td><?= e($row['note']); ?></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="4">No transactions yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
