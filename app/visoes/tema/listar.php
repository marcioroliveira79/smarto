<?php
declare(strict_types=1);

use function App\Lib\CSRF\input as csrf_input;
use function App\Lib\url;

include __DIR__ . '/../partials/cabecalho.php';
?>
<div class="container py-4">
  <h1 class="h4 mb-3" style="color: var(--app-text-accent, #0d6efd);">
    <i class="fa fa-palette me-2" style="color: var(--app-text-accent, #0d6efd);"></i>Tema do Sistema
  </h1>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">Criar/Editar Tema</div>
        <div class="card-body">
          <form method="post" action="<?= url('tema.salvar') ?>" id="formTema">
            <?= csrf_input() ?>
            <input type="hidden" name="id" id="tema-id" value="">
            <div class="mb-2">
              <label class="form-label">Nome do tema</label>
              <input type="text" class="form-control" name="nome" id="tema-nome" required>
            </div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label">Barra de navegação</label>
                <input type="color" class="form-control form-control-color w-100" name="nav" id="c-nav" value="#0d6efd" title="Cor da barra de navegação">
              </div>
              <div class="col-6">
                <label class="form-label">Botões de ação</label>
                <input type="color" class="form-control form-control-color w-100" name="action" id="c-action" value="#0d6efd" title="Novo/Entrar/Editar/Salvar/Aplicar/Gerar/Paginador">
              </div>
              <div class="col-6">
                <label class="form-label">Textos e ícones</label>
                <input type="color" class="form-control form-control-color w-100" name="text_accent" id="c-text" value="#0d6efd" title="Textos e ícones de telas">
              </div>
              <div class="col-6">
                <label class="form-label">Botão Voltar</label>
                <input type="color" class="form-control form-control-color w-100" name="back" id="c-back" value="#6c757d" title="Voltar (outline)">
              </div>
              <div class="col-6">
                <label class="form-label">Botão Excluir</label>
                <input type="color" class="form-control form-control-color w-100" name="delete" id="c-delete" value="#dc3545" title="Excluir">
              </div>
            </div>
            <div class="d-flex gap-2 mt-3">
              <button type="submit" class="btn btn-success"><i class="fa fa-floppy-disk me-1"></i>Salvar</button>
              <button type="button" class="btn btn-primary" id="btnPreview"><i class="fa fa-eye me-1"></i>Pré-visualizar</button>
              <button type="button" class="btn btn-outline-secondary" id="btnReset"><i class="fa fa-rotate-left me-1"></i>Reverter</button>
            </div>
            <div class="form-text mt-2">A cor só é aplicada globalmente quando você clica em “Aplicar” em um tema salvo.</div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">Temas Salvos</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr><th>Nome</th><th>Cores</th><th class="text-end">Ações</th></tr>
              </thead>
              <tbody>
              <?php foreach ($temas as $t): $cores = is_array($t['cores']) ? $t['cores'] : json_decode((string)$t['cores'], true); ?>
                <tr>
                  <td><?= htmlspecialchars((string)$t['nome']) ?> <?= ($ativo_id === (int)$t['id']) ? '<span class="badge bg-success ms-1">Ativo</span>' : '' ?></td>
                  <td>
                    <?php foreach (['nav','action','text_accent','back','delete'] as $k): $v = $cores[$k] ?? ''; if ($v): ?>
                      <span class="d-inline-flex align-items-center me-2"><span class="rounded-circle me-1" style="display:inline-block;width:14px;height:14px;background:<?= htmlspecialchars($v) ?>;"></span><small><?= htmlspecialchars($k) ?></small></span>
                    <?php endif; endforeach; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group">
                      <button class="btn btn-sm btn-outline-secondary btn-edit" data-id="<?= (int)$t['id'] ?>" data-nome="<?= htmlspecialchars((string)$t['nome']) ?>" data-cores='<?= htmlspecialchars(json_encode($cores)) ?>'><i class="fa fa-pen"></i></button>
                      <form method="post" action="<?= url('tema.aplicar') ?>" onsubmit="return confirm('Aplicar este tema para todo o sistema?')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-check"></i> Aplicar</button>
                      </form>
                      <form method="post" action="<?= url('tema.excluir') ?>" onsubmit="return confirm('Excluir este tema?')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit"><i class="fa fa-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-primary"><i class="fa fa-plus me-1"></i>Novo</button>
              <a href="<?= url('dashboard.inicio') ?>" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const form = document.getElementById('formTema');
  const fields = {
    id: document.getElementById('tema-id'),
    nome: document.getElementById('tema-nome'),
    nav: document.getElementById('c-nav'),
    action: document.getElementById('c-action'),
    text: document.getElementById('c-text'),
    back: document.getElementById('c-back'),
    del: document.getElementById('c-delete'),
  };
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function(){
      const cores = JSON.parse(this.getAttribute('data-cores')||'{}');
      fields.id.value = this.getAttribute('data-id')||'';
      fields.nome.value = this.getAttribute('data-nome')||'';
      fields.nav.value = cores.nav || '#0d6efd';
      fields.action.value = cores.action || '#0d6efd';
      fields.text.value = cores.text_accent || '#0d6efd';
      fields.back.value = cores.back || '#6c757d';
      fields.del.value = cores.delete || '#dc3545';
      window.scrollTo({top:0, behavior:'smooth'});
    });
  });

  // Pré-visualização local (não aplica globalmente)
  const btnPreview = document.getElementById('btnPreview');
  const btnReset = document.getElementById('btnReset');
  const root = document.documentElement;
  const old = {
    nav: getComputedStyle(root).getPropertyValue('--color-nav'),
    action: getComputedStyle(root).getPropertyValue('--color-action'),
    text: getComputedStyle(root).getPropertyValue('--color-text-accent'),
    back: getComputedStyle(root).getPropertyValue('--color-back'),
    del: getComputedStyle(root).getPropertyValue('--color-delete')
  };

  function setVar(name, val){ root.style.setProperty(name, val); }
  function hex(n){ return n && n.value ? n.value : '#000000'; }
  function adjust(hexColor, delta){
    const h = hexColor.replace('#','');
    const r = parseInt(h.substring(0,2),16), g=parseInt(h.substring(2,4),16), b=parseInt(h.substring(4,6),16);
    function clamp(v){ return Math.max(0, Math.min(255,v)); }
    function toHex(v){ const s=clamp(v).toString(16).padStart(2,'0'); return s; }
    const rr = toHex(r+delta), gg=toHex(g+delta), bb=toHex(b+delta);
    return '#'+rr+gg+bb;
  }
  btnPreview.addEventListener('click', function(){
    const nav = hex(fields.nav); const act = hex(fields.action); const txt = hex(fields.text); const back = hex(fields.back); const del = hex(fields.del);
    setVar('--color-nav', nav);
    setVar('--color-nav-hover', adjust(nav, -10));
    setVar('--color-nav-active', adjust(nav, -20));
    setVar('--color-action', act);
    setVar('--color-action-hover', adjust(act, -10));
    setVar('--color-action-active', adjust(act, -20));
    setVar('--color-text-accent', txt);
    setVar('--color-back', back);
    setVar('--color-back-hover', adjust(back, -10));
    setVar('--color-delete', del);
    setVar('--color-delete-hover', adjust(del, -10));
  });
  btnReset.addEventListener('click', function(){
    setVar('--color-nav', old.nav);
    setVar('--color-action', old.action);
    setVar('--color-text-accent', old.text);
    setVar('--color-back', old.back);
    setVar('--color-delete', old.del);
  });
})();
</script>
<?php include __DIR__ . '/../partials/rodape.php'; ?>

