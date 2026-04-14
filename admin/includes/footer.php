    </main>
</div><!-- /.admin-content-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $adminRoot ?? '../' ?>js/admin.js"></script>
<?php if (isset($extraJs)): foreach ($extraJs as $js): ?>
<script src="<?= $adminRoot ?? '../' ?>js/<?= htmlspecialchars($js) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
