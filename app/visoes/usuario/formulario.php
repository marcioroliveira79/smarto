<?php
declare(strict_types=1);

use function App\Lib\url;
use function App\Lib\CSRF\input as csrf_input;
use function App\Lib\Tema\icone;

include __DIR__ . '/../partials/cabecalho.php';

$isEdit = !empty($usuario);
?>
<div class="container py-4">
  <h1 class="h4 app-accent mb-3"><i class="fa <?= icone('usuario') ?> me-2"></i>Usuários</h1>
</div>
<div class="card shadow-sm">
  <div class="card-header app-accent"><i class="fa <?= icone('usuario') ?> me-1"></i><?= $isEdit ? 'Editar Usuário' : 'Novo Usuário' ?></div>
  <form method="post" action="<?= url('usuario.salvar') ?>" class="needs-validation" novalidate>
    <?= csrf_input() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>"><?php endif; ?>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nome</label>
          <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Perfil</label>
          <select name="perfil_id" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($perfis as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= isset($usuario['perfil_id']) && (int)$usuario['perfil_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Telefone</label>
          <input type="text" name="telefone" id="f-telefone" class="form-control" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" required placeholder="(99) 99999-9999" maxlength="20">
        </div>
        <div class="col-md-2">
          <label class="form-label">Senha</label>
          <input type="password" name="senha" class="form-control" <?= $isEdit ? '' : 'required' ?> maxlength="14">
        </div>
        <div class="col-md-2">
          <label class="form-label">Verificar Senha</label>
          <input type="password" name="senha2" class="form-control" <?= $isEdit ? '' : 'required' ?> maxlength="14">
        </div>
        <div class="col-md-3 form-check mt-4">
          <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= !isset($usuario['ativo']) || $usuario['ativo'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="ativo">Ativo</label>
        </div>
      </div>
    </div>
  </form>
  <div class="card-footer d-flex gap-2">
    <a href="<?= url('usuario.listar') ?>" class="btn btn-outline-secondary app-link"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
    <button form="" type="submit" class="btn btn-success app-action" onclick="this.closest('.card').querySelector('form').submit();return false;"><i class="fa fa-floppy-disk me-1"></i>Salvar</button>
    <?php if ($isEdit): ?>
      <form method="post" action="<?= url('usuario.excluir') ?>" class="d-inline">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">
        <button type="submit" class="btn btn-danger app-action" data-confirm="Deseja excluir?"><i class="fa fa-trash me-1"></i>Excluir</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../partials/rodape.php'; ?>

<script>
  (function(){
    const tel = document.getElementById('f-telefone');
    if(!tel) return;
    const format = v => {
      v = (v||'').replace(/\D/g,'').slice(0,11);
      if (v.length <= 10) {
        // fixo (xx) xxxx-xxxx
        return v.replace(/^(\d{0,2})(\d{0,4})(\d{0,4}).*/, function(_,a,b,c){
          return (a? '('+a+(a.length==2?') ':''):'') + (b) + (c?'-'+c:'');
        });
      } else {
        // celular (xx) xxxxx-xxxx
        return v.replace(/^(\d{0,2})(\d{0,5})(\d{0,4}).*/, function(_,a,b,c){
          return (a? '('+a+(a.length==2?') ':''):'') + (b) + (c?'-'+c:'');
        });
      }
    };
    tel.addEventListener('input', ()=>{ tel.value = format(tel.value); });
    tel.value = format(tel.value);
  })();
</script>
