<?php
declare(strict_types=1);

use function App\Lib\CSRF\input as csrf_input;
use function App\Lib\url;

$flash = $flash ?? App\Lib\get_flash();
// Nome da empresa
$__app_name = 'Smarto';
try {
  $__n = \App\Modelos\Config\obter('Variavel de Ambiente','NomeEmpresa');
  if (is_string($__n) && trim($__n) !== '') { $__app_name = trim($__n); }
} catch (\Throwable $e) {}

// Mensagem/justificativa opcional
$just = (string)(\App\Modelos\Config\obter('Variavel de Ambiente','ForcaLocGeo_Justificativa') ?? 'Por segurança, precisamos confirmar sua localização para concluir o acesso.');
// Timeout (ms)
$timeoutMs = (int)((\App\Modelos\Config\obter('Variavel de Ambiente','ForcaLocGeo_TimeoutS') ?? 12) * 1000);
if ($timeoutMs <= 0) { $timeoutMs = 12000; }
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Confirmação de Localização - <?= htmlspecialchars($__app_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <style>
    .app-accent, .app-accent * { color: var(--app-text-accent, #0d6efd) !important; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
          <div class="card-header app-accent"><i class="fa fa-location-dot me-2"></i>Confirmação de Localização</div>
          <div class="card-body">
            <?php if ($flash): ?>
              <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>" role="alert">
                <?= htmlspecialchars($flash['mensagem']) ?>
              </div>
            <?php endif; ?>

            <p class="mb-3"><?= htmlspecialchars($just) ?></p>

            <div id="status" class="text-muted small mb-3">Solicitando permissão de localização…</div>

            <form id="locForm" method="post" action="<?= url('autenticacao.finalizar') ?>">
              <?= csrf_input() ?>
              <input type="hidden" name="nonce" value="<?= htmlspecialchars((string)($_SESSION['login_pendente']['nonce'] ?? '')) ?>">
              <input type="hidden" name="perm">
              <input type="hidden" name="lat">
              <input type="hidden" name="lon">
              <input type="hidden" name="acc">
              <input type="hidden" name="capturado_em">
              <button type="submit" class="btn app-action w-100" style="background-color: var(--app-action, #212529); border-color: var(--app-action, #212529); color: #fff;">Confirmar localização</button>
            </form>

            <button class="btn btn-outline-secondary w-100" id="btnRetry" type="button"><i class="fa fa-rotate me-1"></i>Tentar novamente</button>
            <div class="form-text mt-2">Se negar a permissão, não será possível concluir o login.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
  (function(){
    const form = document.getElementById('locForm');
    const statusEl = document.getElementById('status');
    const btnRetry = document.getElementById('btnRetry');
    const submitBtn = document.getElementById('btnSubmit');
    let timedOut = false;

    function setStatus(msg, cls){
      statusEl.textContent = msg;
      statusEl.className = 'small mb-3 ' + (cls || 'text-muted');
    }

    function capture(){
      setStatus('Solicitando permissão de localização…', 'text-muted small mb-3');
      timedOut = false;
      const timeout = setTimeout(function(){
        timedOut = true;
        setStatus('Tempo esgotado ao obter a localização. Verifique as permissões e tente novamente.', 'text-danger small mb-3');
      }, <?= (int)$timeoutMs ?>);

      if (!('geolocation' in navigator)){
        setStatus('Geolocalização não suportada neste dispositivo/navegador.', 'text-danger small mb-3');
        return;
      }
      navigator.geolocation.getCurrentPosition(function(pos){
        if (timedOut) return;
        clearTimeout(timeout);
        const c = pos.coords;
        form.perm.value = 'granted';
        form.lat.value = (c.latitude || '').toString();
        form.lon.value = (c.longitude || '').toString();
        form.acc.value = (c.accuracy || '').toString();
        form.capturado_em.value = new Date().toISOString();
        setStatus('Localização capturada. Concluindo login…', 'text-success small mb-3');
        form.submit();
      }, function(err){
        if (timedOut) return;
        clearTimeout(timeout);
        const code = (err && err.code) || 0;
        form.perm.value = (code === 1) ? 'denied' : 'unavailable';
        setStatus('Não foi possível obter a localização (' + form.perm.value + '). Verifique as permissões e tente novamente.', 'text-danger small mb-3');
      }, { enableHighAccuracy: true, timeout: <?= (int)$timeoutMs ?>, maximumAge: 0 });
    }

    btnRetry.addEventListener('click', function(){ capture(); });
    capture();
  })();
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

