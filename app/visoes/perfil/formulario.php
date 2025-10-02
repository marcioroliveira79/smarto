<?php
declare(strict_types=1);

use function App\Lib\url;
use function App\Lib\CSRF\input as csrf_input;
use function App\Lib\Tema\icone;

include __DIR__ . '/../partials/cabecalho.php';

$isEdit = !empty($perfil);
?>
<div class="container py-4">
  <h1 class="h4 mb-3" style="color: var(--app-text-accent, #0d6efd);">
    <i class="fa <?= icone('perfil') ?> me-2" style="color: var(--app-text-accent, #0d6efd);"></i>Perfis
  </h1>
</div>
<div class="card shadow-sm">
  <div class="card-header app-accent"><i class="fa <?= icone('perfil') ?> me-1"></i><?= $isEdit ? 'Editar Perfil' : 'Novo Perfil' ?></div>
  <form method="post" action="<?= url('perfil.salvar') ?>" class="needs-validation" novalidate>
    <?= csrf_input() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$perfil['id'] ?>"><?php endif; ?>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nome</label>
          <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($perfil['nome'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Descrição</label>
          <input type="text" name="descricao" class="form-control" value="<?= htmlspecialchars($perfil['descricao'] ?? '') ?>">
        </div>
        <div class="col-md-3 form-check mt-4">
          <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= !isset($perfil['ativo']) || $perfil['ativo'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="ativo">Ativo</label>
        </div>
      </div>
    </div>
  </form>
  <div class="card-footer d-flex gap-2">
    <a href="<?= url('perfil.listar') ?>" class="btn btn-outline-secondary app-link"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
    <button form="" type="submit" class="btn btn-success app-action" onclick="this.closest('.card').querySelector('form').submit();return false;"><i class="fa fa-floppy-disk me-1"></i>Salvar</button>
    <?php if ($isEdit): ?>
      <form method="post" action="<?= url('perfil.excluir') ?>" class="d-inline">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= (int)$perfil['id'] ?>">
        <button type="submit" class="btn btn-danger app-action" data-confirm="Deseja excluir?"><i class="fa fa-trash me-1"></i>Excluir</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../partials/rodape.php'; ?>
