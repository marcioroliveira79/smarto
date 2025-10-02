<?php
declare(strict_types=1);

use function App\Lib\url;
use function App\Lib\CSRF\input as csrf_input;

include __DIR__ . '/../partials/cabecalho.php';
?>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fa fa-database me-1"></i>Backup do banco (PLAIN .sql)</div>
  </div>
  <div class="card-body">
    <form method="post" action="<?= url('backup.gerar') ?>" class="mb-3">
      <?= csrf_input() ?>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="incluir_dados" id="incluir_dados" checked>
        <label class="form-check-label" for="incluir_dados">Incluir dados (usa INSERTs)</label>
      </div>
      <button class="btn btn-primary mt-2"><i class="fa fa-play me-1"></i>Gerar backup</button>
    </form>

    <h6>Arquivos gerados</h6>
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr>
            <th>Arquivo</th>
            <th>Tamanho</th>
            <th>Data</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($arquivos ?? []) as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['nome']) ?></td>
              <td><?= number_format((int)$a['tamanho']/1024, 2, ',', '.') ?> KB</td>
              <td><?= htmlspecialchars($a['data']) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="<?= url('backup.baixar', ['arquivo' => $a['nome']]) ?>" title="Baixar"><i class="fa fa-download"></i></a>
                <form method="post" action="<?= url('backup.excluir') ?>" class="d-inline" onsubmit="return confirm('Excluir este backup?');">
                  <?= csrf_input() ?>
                  <input type="hidden" name="arquivo" value="<?= htmlspecialchars($a['nome']) ?>">
                  <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="fa fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">
    <a href="<?= url('dashboard.inicio') ?>" class="btn btn-outline-secondary app-link"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
  </div>
</div>

<?php include __DIR__ . '/../partials/rodape.php'; ?>
