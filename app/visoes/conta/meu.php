<?php
declare(strict_types=1);

use function App\Lib\url;
use function App\Lib\CSRF\input as csrf_input;

include __DIR__ . '/../partials/cabecalho.php';
?>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fa fa-user-gear me-1"></i>Minha Conta</div>
  </div>
  <form method="post" action="<?= url('conta.salvar') ?>" class="needs-validation" novalidate>
    <?= csrf_input() ?>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nome</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" disabled>
        </div>
        <div class="col-md-6">
          <label class="form-label">E-mail</label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" disabled>
        </div>
        <div class="col-md-4">
          <label class="form-label">Perfil</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($perfil_nome) ?>" disabled>
        </div>
        <div class="col-md-4">
          <label class="form-label">Telefone</label>
          <input type="text" name="telefone" id="f-telefone" class="form-control" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" required placeholder="(99) 99999-9999" maxlength="20">
        </div>
        <div class="col-md-2">
          <label class="form-label">Nova senha</label>
          <input type="password" name="senha" class="form-control" maxlength="14">
        </div>
        <div class="col-md-2">
          <label class="form-label">Confirmar senha</label>
          <input type="password" name="senha2" class="form-control" maxlength="14">
        </div>
        <div class="col-12 text-muted small">
          Deixe a senha em branco para não alterar.
        </div>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <a href="<?= url('dashboard.inicio') ?>" class="btn btn-outline-secondary app-link"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
      <button type="submit" class="btn btn-success app-action"><i class="fa fa-floppy-disk me-1"></i>Salvar</button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../partials/rodape.php'; ?>

<script>
  (function(){
    const tel = document.getElementById('f-telefone');
    if(!tel) return;
    const format = v => {
      v = (v||'').replace(/\D/g,'').slice(0,11);
      if (v.length <= 10) {
        return v.replace(/^(\d{0,2})(\d{0,4})(\d{0,4}).*/, function(_,a,b,c){
          return (a? '('+a+(a.length==2?') ':''):'') + (b) + (c?'-'+c:'');
        });
      } else {
        return v.replace(/^(\d{0,2})(\d{0,5})(\d{0,4}).*/, function(_,a,b,c){
          return (a? '('+a+(a.length==2?') ':''):'') + (b) + (c?'-'+c:'');
        });
      }
    };
    tel.addEventListener('input', ()=>{ tel.value = format(tel.value); });
    tel.value = format(tel.value);
  })();
</script>
