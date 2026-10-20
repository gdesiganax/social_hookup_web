</main>

<footer class="footer py-4 mt-5">
  <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-heart-fill brand-icon"></i>
      <span class="fw-semibold"><?= e(config_value('app.name', 'HookUp')) ?></span>
      <span class="text-muted small ms-2">&copy; <?= date('Y') ?></span>
    </div>
    <div class="d-flex gap-4 small">
      <a href="#" class="text-decoration-none text-muted">Privacy</a>
      <a href="#" class="text-decoration-none text-muted">Terms</a>
      <a href="#" class="text-decoration-none text-muted">Support</a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(public_path('assets/js/app.js?v=20261014a')) ?>"></script>
</body>
</html>
