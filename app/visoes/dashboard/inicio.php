<?php
declare(strict_types=1);

include __DIR__ . '/../partials/cabecalho.php';
?>
<div class="p-4 bg-white shadow-sm rounded">
  <?php if (!empty($_SESSION['senha_aviso_dias'])): $__d=(int)$_SESSION['senha_aviso_dias']; unset($_SESSION['senha_aviso_dias']); ?>
    <div class="alert alert-warning" role="alert">
      Sua senha expira em <?= $__d ?> dia(s). <a href="<?= \App\Lib\url('conta.meu') ?>" class="alert-link app-link">Trocar agora</a>.
    </div>
  <?php endif; ?>
  <h5 class="mb-3"><i class="fa fa-house me-2"></i>Bem-vindo(a)</h5>
  <p class="text-muted">Use o menu acima para navegar. Sua visibilidade depende do seu perfil.</p>
</div>
<?php include __DIR__ . '/../partials/rodape.php'; ?>
