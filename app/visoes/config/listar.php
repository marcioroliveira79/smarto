<?php
declare(strict_types=1);

use function App\Lib\url;
use function App\Lib\CSRF\input as csrf_input;

include __DIR__ . '/../partials/cabecalho.php';
?>

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fa fa-sliders me-1"></i>Configurações do sistema</div>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalConfig"><i class="fa fa-plus me-1"></i>Novo</button>
  </div>
  <div class="card-body">
    <div class="d-flex justify-content-end mb-2 gap-2">
      <a class="btn btn-sm btn-outline-primary app-link" href="<?= url('config.interface') ?>"><i class="fa fa-sliders me-1"></i>Configurar interface</a>
      <a class="btn btn-sm btn-outline-primary app-link" href="<?= url('config.email') ?>"><i class="fa fa-envelope me-1"></i>Configurar e-mail</a>
    </div>
    <div class="table-responsive">
      <table class="table table-striped datatable">
        <thead>
          <tr>
            <th>Processo</th>
            <th>Chave</th>
            <th>Valor</th>
            <th>Tipo</th>
            <th>Descrição</th>
            <th>Atualizado</th>
            <th>Criado por</th>
            <th>Atualizado por</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach (($configs ?? []) as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['processo']) ?></td>
            <td><?= htmlspecialchars($c['chave']) ?></td>
            <td><code><?= htmlspecialchars((string)$c['valor']) ?></code></td>
            <td><?= htmlspecialchars((string)($c['tipo'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($c['descricao'] ?? '')) ?></td>
            <td><?php $d = $c['atualizado_em'] ?? null; echo $d? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$d))) : ''; ?></td>
            <td><?= htmlspecialchars((string)($c['criado_por_nome'] ?? '')) ?><?php if (!empty($c['criado_em'])): ?> <small class="text-muted">(<?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$c['criado_em']))) ?>)</small><?php endif; ?></td>
            <td><?= htmlspecialchars((string)($c['atualizado_por_nome'] ?? '')) ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-edit
                data-id="<?= (int)$c['id'] ?>"
                data-processo="<?= htmlspecialchars($c['processo']) ?>"
                data-chave="<?= htmlspecialchars($c['chave']) ?>"
                data-tipo="<?= htmlspecialchars((string)($c['tipo'] ?? 'string')) ?>"
                data-valor="<?= htmlspecialchars((string)$c['valor']) ?>"
                data-descricao="<?= htmlspecialchars((string)$c['descricao']) ?>">
                <i class="fa fa-pen"></i>
              </button>
              <form method="post" action="<?= url('config.excluir') ?>" class="d-inline">
                <?= csrf_input() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" data-confirm="Excluir configuração?"><i class="fa fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">
    <a href="<?= url('dashboard.inicio') ?>" class="btn btn-outline-secondary app-link"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
  </div>
</div>

<!-- Modal cadastro/edição -->
<div class="modal fade" id="modalConfig" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= url('config.salvar') ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="id" id="f-id">
        <div class="modal-header"><h5 class="modal-title">Configuração</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Processo</label>
            <input type="text" name="processo" id="f-processo" class="form-control" placeholder="ex.: monitoramento" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Chave</label>
            <input type="text" name="chave" id="f-chave" class="form-control" placeholder="ex.: janela_online_segundos" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Tipo</label>
            <select name="tipo" id="f-tipo" class="form-select">
              <option value="string">string</option>
              <option value="int">int</option>
              <option value="bool">bool</option>
              <option value="json">json</option>
              <option value="seconds">seconds</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Valor</label>
            <input type="text" name="valor" id="f-valor" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">Descrição</label>
            <input type="text" name="descricao" id="f-descricao" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', ()=>{
    const m = new bootstrap.Modal(document.getElementById('modalConfig'));
    document.getElementById('f-id').value = btn.dataset.id || '';
    document.getElementById('f-processo').value = btn.dataset.processo || '';
    document.getElementById('f-chave').value = btn.dataset.chave || '';
    document.getElementById('f-tipo').value = btn.dataset.tipo || 'string';
    document.getElementById('f-valor').value = btn.dataset.valor || '';
    document.getElementById('f-descricao').value = btn.dataset.descricao || '';
    m.show();
  }));
</script>

<?php include __DIR__ . '/../partials/rodape.php'; ?>

