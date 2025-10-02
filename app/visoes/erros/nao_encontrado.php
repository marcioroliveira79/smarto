<?php // View 404 genérica ?>
<?php include __DIR__ . '/../partials/cabecalho.php'; ?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
  <div class="text-center">
    <div class="mb-3">
      <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light border" style="width:96px;height:96px; box-shadow: 0 4px 14px rgba(0,0,0,.08);">
        <i class="fa fa-circle-exclamation text-warning" style="font-size:42px;"></i>
      </span>
    </div>
    <h3 class="fw-semibold mb-2">Não encontrado</h3>
    <p class="text-muted mb-4"><?= htmlspecialchars((string)($mensagem ?? 'O recurso solicitado não foi localizado.')) ?></p>
   
  </div>
</div>

<?php include __DIR__ . '/../partials/rodape.php'; ?>

