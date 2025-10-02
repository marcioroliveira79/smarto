<?php
declare(strict_types=1);

use function App\Lib\CSRF\input as csrf_input;
use function App\Lib\url;
require_once __DIR__ . '/../../modelos/Config.php';
require_once __DIR__ . '/../../biblioteca/tema.php';

$flash = $flash ?? App\Lib\get_flash();
// Valor prÃ©-preenchido do e-mail (mantÃ©m apÃ³s erro)
$email_prefill = $email_prefill ?? ($_SESSION['login_email'] ?? '');
// Nome da empresa
$__app_name = 'Smarto';
try {
  $__n = \App\Modelos\Config\obter('Variavel de Ambiente','NomeEmpresa');  
  if (is_string($__n) && trim($__n) !== '') { $__app_name = trim($__n); }
} catch (\Throwable $e) {}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - <?= htmlspecialchars($__app_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <?php \App\Lib\Tema\render_css_vars(); ?>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><i class="fa fa-hospital me-2"></i><?= htmlspecialchars($__app_name) ?></div>
          <div class="card-body">
            <?php if ($flash): ?>
              <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>" role="alert">
                <?= htmlspecialchars($flash['mensagem']) ?>
              </div>
            <?php endif; ?>
            <form method="post" action="<?= url('autenticacao.entrar') ?>">
              <?= csrf_input() ?>
              <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email_prefill) ?>" required>
              </div>
  <div class="mb-3">
    <label for="senha" class="form-label">Senha</label>
    <input type="password" class="form-control" id="senha" name="senha" maxlength="14" required>
  </div>
  <?php if (!empty($captcha_pergunta) || !empty($_SESSION['captcha_required'])): ?>
  <div class="mb-3">
    <label for="captcha" class="form-label">Confirmação humana</label>
    <div class="input-group">
      <span class="input-group-text" title="Desafio CAPTCHA"><?= htmlspecialchars($captcha_pergunta ?? 'Resolva o desafio') ?></span>
      <input type="text" class="form-control" id="captcha" name="captcha" inputmode="numeric" pattern="[0-9]*" placeholder="Resposta" required>
    </div>
    <div class="form-text">Preencha o resultado para continuar.</div>
  </div>
  <?php endif; ?>
              <button type="submit" class="btn btn-primary w-100 app-action"><i class="fa fa-right-to-bracket me-1"></i>Entrar</button>
            </form>
            <div class="text-center mt-3">
              <a href="<?= App\Lib\url('autenticacao.esqueci') ?>" class="app-link">Esqueci minha senha</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
