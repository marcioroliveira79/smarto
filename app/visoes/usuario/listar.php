<?php
declare(strict_types=1);

use function App\Lib\url;
use function App\Lib\Tema\icone;

include __DIR__ . '/../partials/cabecalho.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="m-0 text-accent">
    <i class="fa <?= icone('usuario') ?> me-2 text-accent"></i>Usuários
  </h5>
  <a href="<?= url('usuario.novo') ?>" class="btn btn-primary app-link"><i class="fa fa-plus me-1"></i>Novo</a>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped datatable">
        <thead>
          <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Telefone</th>
            <th>Perfil</th>
            <th>Ativo</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['nome']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['telefone'] ?? '') ?></td>
            <td><?= htmlspecialchars($u['perfil']) ?></td>
            <td>
              <?php if ($u['ativo']): ?>
                <span class="badge text-bg-success">Ativo</span>
              <?php else: ?>
                <span class="badge text-bg-secondary">Inativo</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= url('usuario.editar', ['id' => (int)$u['id']]) ?>" class="btn btn-sm btn-primary app-link"><i class="fa fa-pen-to-square"></i></a>
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
