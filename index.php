<?php require __DIR__ . '/layout/header.php'; ?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <h2 style="margin:0">Accounts</h2>
    <a class="btn" href="/simple_accounting/account_new.php">+ New Account</a>
  </div>
  <div class="table-wrap">
    <table class="table" style="margin-top:10px;min-width:720px;">
      <thead>
        <tr>
          <th>Name</th>
          <th>Status</th>
          <th>Balance</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT a.id, a.name, a.created_at,
                       COALESCE(SUM(CASE WHEN t.type='credit' THEN t.amount WHEN t.type='debit' THEN -t.amount END),0) AS balance
                FROM accounts a
                LEFT JOIN transactions t ON t.account_id = a.id
                GROUP BY a.id, a.name, a.created_at
                ORDER BY a.created_at DESC";
        $res = db()->query($sql);
        if ($res && $res->num_rows > 0):
          while($row = $res->fetch_assoc()):
            $bal = (float)$row['balance'];
        ?>
          <tr>
            <td><?= e($row['name']); ?></td>
            <td><?= status_badge($bal); ?></td>
            <td class="balance <?= e(balance_class($bal)); ?>"><?= number_format($bal, 2); ?></td>
            <td><?= e(date('Y-m-d', strtotime($row['created_at']))); ?></td>
            <td>
              <a class="btn secondary" href="/simple_accounting/account.php?id=<?= (int)$row['id']; ?>">Open</a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="5">No accounts yet. Create one to get started.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
