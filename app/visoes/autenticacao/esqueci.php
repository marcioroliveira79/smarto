<?php
declare(strict_types=1);

use function App\Lib\CSRF\input as csrf_input;
use function App\Lib\url;

$flash = $flash ?? App\Lib\get_flash();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar acesso</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <style>
    .app-accent, .app-accent * { color: var(--app-text-accent, #0d6efd) !important; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-header app-accent"><i class="fa fa-key me-2"></i>Recuperar acesso</div>
          <div class="card-body">
            <?php if ($flash): ?>
              <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>" role="alert">
                <?= htmlspecialchars($flash['mensagem']) ?>
              </div>
            <?php endif; ?>
            <form method="post" action="<?= url('autenticacao.enviar_reset') ?>">
              <?= csrf_input() ?>
              <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>
              <button type="submit" class="btn app-action w-100" style="background-color: var(--app-action, #212529); border-color: var(--app-action, #212529); color: #fff;"><i class="fa fa-paper-plane me-1"></i>Enviar link</button>
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

