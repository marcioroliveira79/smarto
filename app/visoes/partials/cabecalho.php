<?php

use function App\Lib\usuario_logado;
use function App\Lib\url;
use function App\Lib\get_flash;
use function App\Lib\Tema\icone;
use function App\Lib\Tema\render_css_vars;

require_once __DIR__ . '/../../modelos/Config.php';
require_once __DIR__ . '/../../modelos/Usuario.php';

// TTL da sessÃƒÂ£o (segundos) com fallback de processo
$sess_ttl = 0;
try {
    $ttlVal = \App\Modelos\Config\obter('sistema', 'tempoMaximoSessaoSegundos');
    if ($ttlVal === null) { $ttlVal = \App\Modelos\Config\obter('Variavel de Ambiente', 'tempoMaximoSessaoSegundos'); }
    
    $sess_ttl = (int)($ttlVal !== null ? trim((string)$ttlVal) : 0);
} catch (\Throwable $e) { $sess_ttl = 0; }

$usuario_sessao = usuario_logado();
$flash = $flash ?? get_flash();
// Nome da empresa para branding
$app_name = 'Smarto';
try {
    $n = \App\Modelos\Config\obter('Variavel de Ambiente','NomeEmpresa');
    
    if (is_string($n) && trim($n) !== '') { $app_name = trim($n); }
} catch (\Throwable $e) { /* mantÃƒÂ©m padrÃƒÂ£o */ }
include __DIR__ . '/forca_troca.php';
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($app_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <?php render_css_vars(); ?>
  <style>
    html, body { margin: 0 !important; padding: 0; }
    .btn { transition: filter .15s ease-in-out, transform .02s ease-in-out; }
    .btn:hover { filter: brightness(1.05); }
    .btn:active { transform: scale(0.98); }
    /* Dropdown + submenu styling/positioning (Bootstrap 5 não dá suporte nativo) */
    .navbar .dropdown-menu { border-radius: .6rem; padding: .25rem; box-shadow: 0 10px 25px rgba(0,0,0,.15); border-color: #e9ecef; }
    .navbar .dropdown-menu .dropdown-item { border-radius: .4rem; padding: .5rem .75rem; display: flex; align-items: center; gap: .5rem; }
    .navbar .dropdown-menu .dropdown-item i.fa { width: 1.1rem; text-align: center; opacity: .9; }
    .navbar .dropdown-menu .dropdown-item:hover { background-color: #f2f4f7; }
    .navbar .dropdown-divider { margin: .35rem .25rem; }
    .dropdown-submenu { position: relative; }
    .dropdown-submenu > .dropdown-menu { position: absolute; top: 0; left: 100%; margin-top: -0.25rem; margin-left: .15rem; }
    .dropdown-submenu > .dropdown-item::after { content: none; }
    @media (hover: hover) { .dropdown-submenu:hover > .dropdown-menu { display: block; } }
    /* Classe para textos e ícones destacados */
    .text-accent { color: var(--color-text-accent, #0d6efd) !important; }
  </style>
</head>
<body style="margin:0!important;padding:0;">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary" style="margin-top:0!important;">
  <div class="container-fluid">
    <a class="navbar-brand app-brand" href="<?= url('dashboard.inicio') ?>"><i class="fa <?= icone('home') ?> me-2"></i><?= htmlspecialchars($app_name) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbars" aria-controls="navbars" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbars">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0" id="nav-menus"></ul>
      <span class="navbar-text me-3 d-flex align-items-center gap-2">
        <?php if ($usuario_sessao): ?>
          <a href="<?= url('conta.meu') ?>" class="link-light text-decoration-none app-link" title="Minha conta">
            <i class="fa fa-user me-1"></i><?= htmlspecialchars($usuario_sessao['nome']) ?>
          </a>
          <?php
            $login_ts = (int)($usuario_sessao['login_ts'] ?? 0);
            $sess_show = false; try {
              $showVal = \App\Modelos\Config\obter('sistema','sessao_mostrar_cronometro');
              if ($showVal === null) { $showVal = \App\Modelos\Config\obter('Variavel de Ambiente','sessao_mostrar_cronometro'); }
              
              if ($showVal !== null) {
                $v = strtolower(trim((string)$showVal));
                $sess_show = in_array($v, ['1','true','t','yes','sim','y','on','habilitado','enabled'], true);
              }
            } catch (\Throwable $e) { $sess_show=false; }
?>

          <small id="session-remaining"
                 class="badge bg-light text-dark"
                 style="font-weight:500; <?= (!$sess_show || $sess_ttl<=0 || $login_ts<=0) ? 'display:none;' : '' ?>"
                 title="Tempo restante da sessão"
                 <?= $sess_ttl > 0 && $login_ts > 0 ? 'data-ttl="'.(int)$sess_ttl.'" data-login-ts="'.(int)$login_ts.'"' : '' ?>>
            <?php if ($sess_ttl > 0 && $login_ts > 0): $__left = max(0, $sess_ttl - (time() - $login_ts)); echo ($__left>=3600 ? gmdate('H:i:s', $__left) : gmdate('i:s', $__left)); endif; ?>
          </small>
        <?php endif; ?>
      </span>
      <?php if ($usuario_sessao): ?>
        <a class="btn btn-outline-light app-link" href="<?= url('autenticacao.sair') ?>"><i class="fa fa-right-from-bracket me-1"></i>Sair</a>
      <?php endif; ?>
    </div>
  </div>
  </nav>

  <div class="container mt-3">
    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>" role="alert">
        <?= htmlspecialchars($flash['mensagem']) ?>
      </div>
    <?php endif; ?>





