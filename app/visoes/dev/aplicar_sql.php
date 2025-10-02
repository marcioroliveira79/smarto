<?php
declare(strict_types=1);

include __DIR__ . '/../partials/cabecalho.php';
?>
<div class="card shadow-sm">
  <div class="card-header"><i class="fa fa-database me-1"></i>Aplicar Migrações e Seed</div>
  <div class="card-body">
    <h6>Migrações</h6>
    <pre class="bg-light p-2 border">
<?php foreach ($res['migracoes'] as $r): ?>
<?= ($r['ok'] ? '[OK] ' : '[ERRO] ') . $r['arquivo'] . (!empty($r['erro']) ? (' - ' . $r['erro']) : '') . "\n" ?>
<?php endforeach; ?>
    </pre>
    <h6>Seeds</h6>
    <pre class="bg-light p-2 border">
<?php foreach ($res['semente'] as $r): ?>
<?= ($r['ok'] ? '[OK] ' : '[ERRO] ') . $r['arquivo'] . (!empty($r['erro']) ? (' - ' . $r['erro']) : '') . "\n" ?>
<?php endforeach; ?>
    </pre>
  </div>
  <div class="card-footer">
    <a href="<?= App\Lib\url('dashboard.inicio') ?>" class="btn btn-outline-secondary app-link"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
  </div>
</div>

<?php include __DIR__ . '/../partials/rodape.php'; ?>

