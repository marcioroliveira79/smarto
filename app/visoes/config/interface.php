<?php
declare(strict_types=1);

use function App\Lib\url;
use function App\Lib\CSRF\input as csrf_input;

include __DIR__ . '/../partials/cabecalho.php';
$ui = $ui ?? [];
?>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fa fa-sliders me-1"></i>Configurar interface</div>
    <a href="<?= url('config.listar') ?>" class="btn btn-sm btn-outline-secondary app-link app-action"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
  </div>
  <div class="card-body">
    <form method="post" action="<?= url('config.interface_salvar') ?>">
      <?= csrf_input() ?>

      <h6 class="text-muted mb-2">Variáveis de Ambiente</h6>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label">Nome da Empresa</label>
          <input type="text" class="form-control" name="NomeEmpresa" value="<?= htmlspecialchars((string)($ui['NomeEmpresa'] ?? 'Smarto')) ?>" required>
          <div class="form-text">Aparece no título da aba, login e canto superior esquerdo.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">URL do Sistema</label>
          <input type="text" class="form-control" name="urlSistema" value="<?= htmlspecialchars((string)($ui['urlSistema'] ?? '')) ?>" placeholder="https://seusite.com/smarto/publico">
          <div class="form-text">Usada em links de e-mail (ex.: redefinição de senha).</div>
        </div>
        <div class="col-sm-4 col-md-3">
          <label class="form-label">Tempo máximo de sessão (s)</label>
          <input type="number" class="form-control" name="tempoMaximoSessaoSegundos" min="0" value="<?= (int)($ui['tempoMaximoSessaoSegundos'] ?? 0) ?>">
        </div>
        <div class="col-sm-4 col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="sistema_em_manutencao" id="f-man" <?= (strtolower((string)($ui['sistema_em_manutencao'] ?? ''))==='true')?'checked':'' ?>>
            <label for="f-man" class="form-check-label">Sistema em manutenção</label>
          </div>
        </div>
        <div class="col-sm-4 col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="sessao_mostrar_cronometro" id="f-crono" <?= (strtolower((string)($ui['sessao_mostrar_cronometro'] ?? ''))==='true')?'checked':'' ?>>
            <label for="f-crono" class="form-check-label">Mostrar cronômetro da sessão</label>
          </div>
        </div>
        <div class="col-sm-4 col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="ForcaLocGeo" id="f-geo" <?= (strtolower((string)($ui['ForcaLocGeo'] ?? ''))==='true')?'checked':'' ?>>
            <label for="f-geo" class="form-check-label">Forçar localização geográfica</label>
          </div>
        </div>
        <div class="col-sm-4 col-md-3">
          <label class="form-label">Forçar troca de senha (dias)</label>
          <input type="number" class="form-control" name="forcaTrocaSenhaDias" min="0" value="<?= (int)($ui['forcaTrocaSenhaDias'] ?? 0) ?>">
        </div>
        <div class="col-sm-4 col-md-3">
          <label class="form-label">Aviso de troca de senha (dias)</label>
          <input type="number" class="form-control" name="aviso_troca_senha" min="0" value="<?= (int)($ui['aviso_troca_senha'] ?? 0) ?>">
        </div>
        <div class="col-sm-4 col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="senhaForte" id="f-strong" <?= (strtolower((string)($ui['senhaForte'] ?? ''))==='true')?'checked':'' ?>>
            <label for="f-strong" class="form-check-label">Exigir senha forte</label>
          </div>
        </div>
      </div>

      <h6 class="text-muted mb-2">Monitoramento</h6>
      <div class="row g-3">
        <div class="col-sm-4 col-md-3">
          <label class="form-label">Atualização dos menus (s)</label>
          <input type="number" class="form-control" name="refresh_menu" min="5" max="3600" value="<?= (int)($ui['refresh_menu'] ?? 30) ?>">
        </div>
        <div class="col-sm-4 col-md-3">
          <label class="form-label">Atualização do dashboard (s)</label>
          <input type="number" class="form-control" name="tempo_atualiza_dash_monitoramento" min="5" value="<?= (int)($ui['tempo_atualiza_dash_monitoramento'] ?? 45) ?>">
        </div>
        <div class="col-sm-4 col-md-3">
          <label class="form-label">Janela de online (s)</label>
          <input type="number" class="form-control" name="janela_online_segundos" min="15" value="<?= (int)($ui['janela_online_segundos'] ?? 120) ?>">
        </div>
      </div>

      <div class="mt-4 text-end">
        <button class="btn btn-primary app-action"><i class="fa fa-floppy-disk me-1"></i>Salvar</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../partials/rodape.php'; ?>

