<?php
?>
  </main>

  <?php if (!empty($currentUser) && empty($hide_bottom_nav)): ?>
    <?php require_once __DIR__ . '/bottom-nav.php'; ?>
  <?php endif; ?>

</div>

<!-- Global App Scripts -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<?php if (!empty($extra_js)): ?>
  <?php foreach ((array)$extra_js as $script): ?>
    <script src="<?= BASE_URL ?>/assets/js/<?= e($script) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
