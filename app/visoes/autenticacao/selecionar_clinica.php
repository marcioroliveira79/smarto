<?php
declare(strict_types=1);

use function App\Lib\CSRF\input as csrf_input;
use function App\Lib\url;

include __DIR__ . '/../partials/cabecalho.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header"><i class="fa fa-hospital me-1"></i>Selecione a clínica</div>
      <div class="card-body">
        <form method="post" action="<?= url('autenticacao.confirmar_clinica') ?>">
          <?= csrf_input() ?>
          <div class="mb-3">
            <label for="clinica" class="form-label">Clínica</label>
            <select id="clinica" name="clinica_id" class="form-select" required>
              <option value="">Selecione</option>
              <?php foreach ($clinicas as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-success app-action"><i class="fa fa-check me-1"></i>Confirmar</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../partials/rodape.php'; ?>

