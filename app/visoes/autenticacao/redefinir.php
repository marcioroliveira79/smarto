<?php
declare(strict_types=1);

use function App\Lib\CSRF\input as csrf_input;
use function App\Lib\url;

$flash = $flash ?? App\Lib\get_flash();
$token = (string)($_GET['token'] ?? ($_POST['token'] ?? ''));
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Definir nova senha</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><i class="fa fa-lock me-2"></i>Definir nova senha</div>
          <div class="card-body">
            <?php if ($flash): ?>
              <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>" role="alert">
                <?= htmlspecialchars($flash['mensagem']) ?>
              </div>
            <?php endif; ?>
            <form method="post" action="<?= url('autenticacao.redefinir_salvar') ?>">
              <?= csrf_input() ?>
              <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
              <div class="mb-3">
                <label class="form-label">Nova senha</label>
                <input type="password" class="form-control" name="senha" maxlength="14" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Confirmar nova senha</label>
                <input type="password" class="form-control" name="senha2" maxlength="14" required>
              </div>
              <button type="submit" class="btn btn-primary w-100 app-action"><i class="fa fa-check me-1"></i>Salvar</button>
            </form>
            <div class="text-center mt-3">
              <a href="<?= url('autenticacao.login') ?>" class="app-link">Voltar para o login</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
