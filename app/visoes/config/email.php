<?php
declare(strict_types=1);

use function App\Lib\url;
use function App\Lib\CSRF\input as csrf_input;

include __DIR__ . '/../partials/cabecalho.php';
$cfg = $cfg ?? [];
?>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fa fa-envelope me-1"></i>Configurações de E-mail</div>
  </div>
  <div class="card-body">
    <form method="post" action="<?= url('config.email_salvar') ?>" id="formEmail">
      <?= csrf_input() ?>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Provedor</label>
          <select name="provider" id="f-provider" class="form-select">
            <option value="gmail" <?= ($cfg['provider'] ?? 'gmail')==='gmail'?'selected':'' ?>>Gmail (SMTP)</option>
            <option value="custom" <?= ($cfg['provider'] ?? '')==='custom'?'selected':'' ?>>Outro SMTP</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">E-mail de envio (From)</label>
          <input type="email" name="from_email" class="form-control" value="<?= htmlspecialchars($cfg['from_email'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nome do remetente</label>
          <input type="text" name="from_name" class="form-control" value="<?= htmlspecialchars($cfg['from_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">SMTP Host</label>
          <input type="text" id="f-smtp-host" name="smtp_host" class="form-control" value="<?= htmlspecialchars($cfg['smtp_host'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Porta</label>
          <input type="number" id="f-smtp-port" name="smtp_port" class="form-control" value="<?= htmlspecialchars((string)($cfg['smtp_port'] ?? '587')) ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Segurança</label>
          <select id="f-smtp-sec" name="smtp_secure" class="form-select">
            <?php $sec = $cfg['smtp_secure'] ?? 'tls'; ?>
            <option value="tls" <?= $sec==='tls'?'selected':'' ?>>TLS (STARTTLS)</option>
            <option value="ssl" <?= $sec==='ssl'?'selected':'' ?>>SSL</option>
            <option value="none" <?= $sec==='none'?'selected':'' ?>>Nenhuma</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Usuário (login)</label>
          <input type="text" id="f-smtp-user" name="smtp_user" class="form-control" value="<?= htmlspecialchars($cfg['smtp_user'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Senha (ou App Password)</label>
          <input type="password" name="smtp_pass" class="form-control" value="" placeholder="Deixe em branco p/ manter atual">
        </div>
        <div class="col-12 small text-muted" id="gmail-hint">
          Para Gmail, use um App Password (conta com 2FA). Host: smtp.gmail.com, Porta: 587, Segurança: TLS.
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-success app-action" type="submit"><i class="fa fa-floppy-disk me-1"></i>Salvar</button>
      </div>
    </form>

    <hr>

    <form method="post" action="<?= url('config.email_teste') ?>" class="row g-2">
      <?= csrf_input() ?>
      <div class="col-md-5">
        <label class="form-label">Enviar e-mail de teste para</label>
        <input type="email" class="form-control" name="destinatario" placeholder="destinatario@exemplo.com" required>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary app-action"><i class="fa fa-paper-plane me-1"></i>Enviar teste</button>
      </div>
    </form>
  </div>
  <div class="card-footer">
    <a href="<?= url('config.listar') ?>" class="btn btn-outline-secondary app-link"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
  </div>
</div>

<?php include __DIR__ . '/../partials/rodape.php'; ?>

<script>
  (function(){
    const provider = document.getElementById('f-provider');
    const host = document.getElementById('f-smtp-host');
    const port = document.getElementById('f-smtp-port');
    const sec = document.getElementById('f-smtp-sec');
    const user = document.getElementById('f-smtp-user');
    const hint = document.getElementById('gmail-hint');
    function applyPreset(){
      if (provider.value === 'gmail') {
        if (!host.value) host.value = 'smtp.gmail.com';
        if (!port.value) port.value = '587';
        // Não forçar a segurança; respeitar o valor salvo/selecionado
        hint.style.display = 'block';
      } else {
        hint.style.display = 'none';
      }
    }
    provider.addEventListener('change', applyPreset);
    applyPreset();
  })();
</script>
