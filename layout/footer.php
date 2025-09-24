      </main>
    </div>
  </div>
  <div id="toast-container"></div>
  <script src="/simple_accounting/assets/app.js"></script>
  <?php $flashes = pop_flashes(); if (!empty($flashes)): ?>
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
