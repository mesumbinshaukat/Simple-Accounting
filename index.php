<?php require __DIR__ . '/layout/header.php'; ?>
<div class="grid grid-2">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
      <h2 style="margin:0">Accounts</h2>
      <a class="btn" href="/simple_accounting/account_new.php">+ New Account</a>
    </div>
    <table class="table" style="margin-top:10px;">
      <thead>
        <tr>
          <th>Name</th>
          <th>Balance</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT a.id, a.name, COALESCE(SUM(CASE WHEN t.type='credit' THEN t.amount WHEN t.type='debit' THEN -t.amount END),0) AS balance
                FROM accounts a
                LEFT JOIN transactions t ON t.account_id = a.id
                GROUP BY a.id, a.name
                ORDER BY a.created_at DESC";
        $res = db()->query($sql);
        if ($res && $res->num_rows > 0):
          while($row = $res->fetch_assoc()):
        ?>
          <tr>
            <td><?= e($row['name']); ?></td>
            <td class="balance"><?= number_format((float)$row['balance'], 2); ?></td>
            <td>
              <a class="btn secondary" href="/simple_accounting/account.php?id=<?= (int)$row['id']; ?>">Open</a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="3">No accounts yet. Create one to get started.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card">
    <h2 style="margin-top:0">Quick Tips</h2>
    <p>Use the sidebar to create a new account. Inside an account, you can perform debit and credit operations and see a running total.</p>
    <span class="badge">Light theme • Minimal UI • Responsive</span>
  </div>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
