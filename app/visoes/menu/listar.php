<?php
// removed strict_types in view to avoid first-statement constraint

use function App\Lib\url;
use function App\Lib\CSRF\input as csrf_input;

include __DIR__ . '/../partials/cabecalho.php';
?>

<style>
.menu-card { width: 60%; max-width: 980px; margin: 0 auto; }
.tree { line-height: 1.4; }
.tree ul { list-style: none; padding-left: .75rem; margin: 0; border-left: 1px dashed #d9dee3; margin-left: .5rem; }
.tree > ul { border-left: none; margin-left: 0; padding-left: 0; }
.tree li { margin: .25rem 0; }
.tree li { position: relative; }
.tree ul ul li::before { content:''; position:absolute; top: .9rem; left: -.5rem; width: .6rem; border-top: 1px dashed #d9dee3; }
.tree .node { display: flex; align-items: center; gap: .35rem; width: 100%; }
.tree .toggle { display: none; }
.tree .name { cursor: default; }
.tree .iconbar { display: inline-flex; gap: .25rem; align-items: center; margin-left: 0; flex: 0 0 var(--iconbar-w, 160px); width: var(--iconbar-w, 160px); justify-content: flex-end; }
.tree .iconbar::before { content: ''; flex: 1 1 auto; height: 0; border-top: 1px dashed #d9dee3; margin-right: 0; }
.tree .iconbar .btn { padding: .1rem .3rem; font-size: .8rem; line-height: 1; }
.tree .chip { background: #e9ecef; color: #495057; border-radius: .5rem; padding: .05rem .35rem; font-size: .75rem; }
.tree .route { font-size: .75rem; color: #6c757d; }
.tree .muted { opacity: .6; }
.tree .branch > ul { display: none; }
.tree .branch.expanded > ul { display: block; }
.tree .spacer { flex: 1 1 auto; min-width: 0; height: 0; border-top: 1px dashed #d9dee3; margin: 0 -0.35rem 0 0; align-self: center; }

/* Destaque de item movido e hover */
li[data-tipo="item"] > .node { border-radius: .25rem; }
li[data-tipo="item"].is-moved > .node { background: #f1f3f5; }
li[data-tipo="item"] > .node:hover { background: #e9ecef; }
li[data-tipo="submenu"] > .node { border-radius: .25rem; }
li[data-tipo="submenu"].is-moved > .node { background: #f1f3f5; }
li[data-tipo="submenu"] > .node:hover { background: #e9ecef; }
li[data-tipo="menu"] > .node { border-radius: .25rem; }
li[data-tipo="menu"].is-moved > .node { background: #f1f3f5; }
li[data-tipo="menu"] > .node:hover { background: #e9ecef; }

/* Seletor de Ã­cones */
.icon-grid { display: grid; grid-template-columns: repeat(8, minmax(40px, 1fr)); gap: .5rem; max-height: 240px; overflow: auto; border: 1px solid #dee2e6; border-radius: .25rem; padding: .5rem; background: #fff; }
.icon-cell { border: 1px solid #dee2e6; border-radius: .25rem; display: flex; align-items: center; justify-content: center; padding: .4rem; cursor: pointer; background: #fff; }
.icon-cell:hover { background: #f8f9fa; }
.icon-cell.active { outline: 2px solid #0d6efd; background: #e7f1ff; }
#f-icone { display: none; }

/* Ãrea de drop visÃ­vel mesmo quando vazia */
li.branch > ul:empty { min-height: .6rem; }
.drop-allow { background: #e7f1ff; outline: 1px dashed #0d6efd; }
.drop-deny  { background: #fdeaea; outline: 1px dashed #dc3545; }

/* Modais mais compactos (metade da largura em telas maiores) */
#modalForm .modal-dialog, #modalPerm .modal-dialog { width: clamp(320px, 50vw, 520px); max-width: 100%; }
</style>

<div class="card shadow-sm menu-card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fa fa-sitemap me-1"></i>Menus, Submenus e Itens</div>
    <div>
      <button class="btn btn-sm btn-primary" data-action="novo-menu"><i class="fa fa-plus me-1"></i>Novo menu</button>
    </div>
  </div>
  <div class="card-body">
    <div class="tree" id="menu-tree">
      <ul>
        <?php foreach ($arvore as $mi => $m): $menusCount = count($arvore); $isFirstM = ($mi===0); $isLastM = ($mi===$menusCount-1); ?>
        <li class="branch expanded" data-tipo="menu" data-id="<?= (int)$m['id'] ?>" data-menu-id="<?= (int)$m['id'] ?>">
          <div class="node">
            <span class="toggle" title="Expandir/retrair"></span>
            <i class="fa <?= htmlspecialchars($m['icone'] ?: 'fa-bars') ?> text-primary"></i>
            <span class="name fw-semibold"><?= htmlspecialchars($m['nome']) ?></span>
            
            <?php if (empty($m['ativo'])): ?><span class="text-muted">(inativo)</span><?php endif; ?>
            <span class="spacer"></span>
            <span class="iconbar ms-1">
              <button class="btn btn-outline-primary" title="Novo submenu" data-action="novo-submenu" data-menu-id="<?= (int)$m['id'] ?>"><i class="fa fa-folder-plus"></i></button>
              <button class="btn btn-outline-primary" title="Novo item" data-action="novo-item" data-menu-id="<?= (int)$m['id'] ?>" data-submenu-id="0"><i class="fa fa-square-plus"></i></button>
              <button class="btn btn-outline-secondary" title="Editar" data-action="editar-menu"
                data-id="<?= (int)$m['id'] ?>" data-nome="<?= htmlspecialchars($m['nome']) ?>" data-icone="<?= htmlspecialchars($m['icone'] ?? '') ?>" data-ordem="<?= (int)($m['ordem'] ?? 0) ?>" data-ativo="<?= (int)($m['ativo'] ?? 1) ?>">
                <i class="fa fa-pen"></i></button>
              <form method="post" action="<?= url('menu.reordenar') ?>" class="d-inline">
                <?= csrf_input() ?>
                <input type="hidden" name="tipo" value="menu"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <input type="hidden" name="dir" value="up">
                <button class="btn btn-outline-secondary" title="Mover para cima" <?= $isFirstM ? 'disabled style="opacity:.35; pointer-events:none;"' : '' ?>><i class="fa fa-chevron-up"></i></button>
              </form>
              <form method="post" action="<?= url('menu.reordenar') ?>" class="d-inline">
                <?= csrf_input() ?>
                <input type="hidden" name="tipo" value="menu"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <input type="hidden" name="dir" value="down">
                <button class="btn btn-outline-secondary" title="Mover para baixo" <?= $isLastM ? 'disabled style="opacity:.35; pointer-events:none;"' : '' ?>><i class="fa fa-chevron-down"></i></button>
              </form>
              <form method="post" action="<?= url('menu.excluir') ?>" class="d-inline" onsubmit="return confirm('Excluir menu e tudo abaixo?')">
                <?= csrf_input() ?>
                <input type="hidden" name="tipo" value="menu"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button class="btn btn-outline-danger" title="Excluir"><i class="fa fa-trash"></i></button>
              </form>
            </span>
          </div>
          <?php // nÃ­vel combinado: itens de raiz + submenus ?>
          <?php $nivel = [];
                foreach ($m['itens'] as $tmpI) { $nivel[] = ['tipo'=>'item','id'=>(int)$tmpI['id'],'ordem'=>(int)($tmpI['ordem'] ?? 0)]; }
                foreach ($m['submenus'] as $tmpS) { $nivel[] = ['tipo'=>'submenu','id'=>(int)$tmpS['id'],'ordem'=>(int)($tmpS['ordem'] ?? 0)]; }
                usort($nivel, function($a,$b){ if ($a['ordem']===$b['ordem']) return $a['tipo']<=>$b['tipo'] ?: $a['id']<=>$b['id']; return $a['ordem']<=>$b['ordem']; });
                $posMap = []; foreach ($nivel as $idx=>$n) { $posMap[$n['tipo'].'-'.$n['id']] = $idx; }
                $nivelCount = count($nivel);
           ?>
          <ul>
            <?php foreach ($m['itens'] as $ii => $it): $pos = $posMap['item-'.(int)$it['id']] ?? -1; $isFirstNL = ($pos === 0); $isLastNL = ($pos === $nivelCount-1); ?>
              <li data-tipo="item" data-id="<?= (int)$it['id'] ?>" data-ordem="<?= (int)($it['ordem'] ?? 0) ?>" draggable="true" data-menu-id="<?= (int)$m['id'] ?>" data-submenu-id="0">
                <div class="node">
                  <span class="toggle muted"></span>
                  <i class="fa <?= htmlspecialchars($it['icone'] ?: 'fa-circle') ?> text-info"></i>
                  <span class="name"><?= htmlspecialchars($it['nome']) ?></span>
                  
                  
                  <?php if (empty($it['ativo'])): ?><span class="text-muted">(inativo)</span><?php endif; ?>
                  <span class="spacer"></span>
                  <span class="iconbar ms-1">
                    <button class="btn btn-outline-secondary" title="Editar" data-action="editar-item"
                      data-id="<?= (int)$it['id'] ?>" data-menu-id="<?= (int)$m['id'] ?>" data-submenu-id="0"
                      data-nome="<?= htmlspecialchars($it['nome']) ?>" data-icone="<?= htmlspecialchars($it['icone'] ?? '') ?>" data-rota="<?= htmlspecialchars($it['rota_acao']) ?>" data-ordem="<?= (int)($it['ordem'] ?? 0) ?>" data-ativo="<?= (int)($it['ativo'] ?? 1) ?>">
                      <i class="fa fa-pen"></i></button>
                    <button class="btn btn-outline-dark" title="PermissÃµes" data-action="permissoes-item" data-item-id="<?= (int)$it['id'] ?>">
                      <i class="fa fa-user-shield"></i></button>
                    <form method="post" action="<?= url('menu.reordenar_nivel') ?>" class="d-inline">
                      <?= csrf_input() ?>
                      <input type="hidden" name="menu_id" value="<?= (int)$m['id'] ?>">
                      <input type="hidden" name="tipo" value="item"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                      <input type="hidden" name="dir" value="up">
                      <button class="btn btn-outline-secondary" title="Mover para cima" <?= $isFirstNL ? 'disabled style="opacity:.35; pointer-events:none;"' : '' ?>><i class="fa fa-chevron-up"></i></button>
                    </form>
                    <form method="post" action="<?= url('menu.reordenar_nivel') ?>" class="d-inline">
                      <?= csrf_input() ?>
                      <input type="hidden" name="menu_id" value="<?= (int)$m['id'] ?>">
                      <input type="hidden" name="tipo" value="item"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                      <input type="hidden" name="dir" value="down">
                      <button class="btn btn-outline-secondary" title="Mover para baixo" <?= $isLastNL ? 'disabled style="opacity:.35; pointer-events:none;"' : '' ?>><i class="fa fa-chevron-down"></i></button>
                    </form>
                    <form method="post" action="<?= url('menu.excluir') ?>" class="d-inline" onsubmit="return confirm('Excluir item?')">
                      <?= csrf_input() ?>
                      <input type="hidden" name="tipo" value="item"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                      <input type="hidden" name="menu_id" value="<?= (int)$m['id'] ?>">
                      <button class="btn btn-outline-danger" title="Excluir"><i class="fa fa-trash"></i></button>
                    </form>
                  </span>
                </div>
              </li>
            <?php endforeach; ?>

            <?php foreach ($m['submenus'] as $si => $sm): $posS = $posMap['submenu-'.(int)$sm['id']] ?? -1; $isFirstSNL = ($posS === 0); $isLastSNL = ($posS === $nivelCount-1); ?>
            <li class="branch expanded" data-tipo="submenu" data-id="<?= (int)$sm['id'] ?>" data-ordem="<?= (int)($sm['ordem'] ?? 0) ?>" data-menu-id="<?= (int)$m['id'] ?>" draggable="true">
              <div class="node">
                <span class="toggle" title="Expandir/retrair"></span>
                <i class="fa <?= htmlspecialchars($sm['icone'] ?: 'fa-folder-open') ?> text-warning"></i>
                <span class="name"><?= htmlspecialchars($sm['nome']) ?></span>
                
                <?php if (empty($sm['ativo'])): ?><span class="text-muted">(inativo)</span><?php endif; ?>
                <span class="spacer"></span>
                <span class="iconbar ms-1">
                  <button class="btn btn-outline-primary" title="Novo item" data-action="novo-item" data-menu-id="<?= (int)$m['id'] ?>" data-submenu-id="<?= (int)$sm['id'] ?>"><i class="fa fa-square-plus"></i></button>
                  <button class="btn btn-outline-secondary" title="Editar" data-action="editar-submenu"
                    data-id="<?= (int)$sm['id'] ?>" data-menu-id="<?= (int)$m['id'] ?>" data-nome="<?= htmlspecialchars($sm['nome']) ?>" data-icone="<?= htmlspecialchars($sm['icone'] ?? '') ?>" data-ordem="<?= (int)($sm['ordem'] ?? 0) ?>" data-ativo="<?= (int)($sm['ativo'] ?? 1) ?>">
                    <i class="fa fa-pen"></i></button>
                  <form method="post" action="<?= url('menu.reordenar_nivel') ?>" class="d-inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="menu_id" value="<?= (int)$m['id'] ?>">
                    <input type="hidden" name="tipo" value="submenu"><input type="hidden" name="id" value="<?= (int)$sm['id'] ?>">
                    <input type="hidden" name="dir" value="up">
                    <button class="btn btn-outline-secondary" title="Mover para cima" <?= $isFirstSNL ? 'disabled style="opacity:.35; pointer-events:none;"' : '' ?>><i class="fa fa-chevron-up"></i></button>
                  </form>
                  <form method="post" action="<?= url('menu.reordenar_nivel') ?>" class="d-inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="menu_id" value="<?= (int)$m['id'] ?>">
                    <input type="hidden" name="tipo" value="submenu"><input type="hidden" name="id" value="<?= (int)$sm['id'] ?>">
                    <input type="hidden" name="dir" value="down">
                    <button class="btn btn-outline-secondary" title="Mover para baixo" <?= $isLastSNL ? 'disabled style="opacity:.35; pointer-events:none;"' : '' ?>><i class="fa fa-chevron-down"></i></button>
                  </form>
                  <form method="post" action="<?= url('menu.excluir') ?>" class="d-inline" onsubmit="return confirm('Excluir submenu e seus itens?')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="tipo" value="submenu"><input type="hidden" name="id" value="<?= (int)$sm['id'] ?>">
                    <input type="hidden" name="menu_id" value="<?= (int)$m['id'] ?>">
                    <button class="btn btn-outline-danger" title="Excluir"><i class="fa fa-trash"></i></button>
                  </form>
                </span>
              </div>
              <ul>
                <?php foreach ($sm['itens'] as $ji => $it): $jcount = count($sm['itens']); $isFirstIt = ($ji===0); $isLastIt = ($ji===$jcount-1); ?>
                <li data-tipo="item" data-id="<?= (int)$it['id'] ?>" draggable="true" data-menu-id="<?= (int)$m['id'] ?>" data-submenu-id="<?= (int)$sm['id'] ?>">
                  <div class="node">
                    <span class="toggle muted"></span>
                    <i class="fa <?= htmlspecialchars($it['icone'] ?: 'fa-circle') ?> text-info"></i>
                    <span class="name"><?= htmlspecialchars($it['nome']) ?></span>
                    
                    
                    <?php if (empty($it['ativo'])): ?><span class="text-muted">(inativo)</span><?php endif; ?>
                    <span class="spacer"></span>
                    <span class="iconbar ms-1">
                      <button class="btn btn-outline-secondary" title="Editar" data-action="editar-item"
                        data-id="<?= (int)$it['id'] ?>" data-menu-id="<?= (int)$m['id'] ?>" data-submenu-id="<?= (int)$sm['id'] ?>"
                        data-nome="<?= htmlspecialchars($it['nome']) ?>" data-icone="<?= htmlspecialchars($it['icone'] ?? '') ?>" data-rota="<?= htmlspecialchars($it['rota_acao']) ?>" data-ordem="<?= (int)($it['ordem'] ?? 0) ?>" data-ativo="<?= (int)($it['ativo'] ?? 1) ?>">
                        <i class="fa fa-pen"></i></button>
                      <button class="btn btn-outline-dark" title="PermissÃµes" data-action="permissoes-item" data-item-id="<?= (int)$it['id'] ?>">
                        <i class="fa fa-user-shield"></i></button>
                      <form method="post" action="<?= url('menu.reordenar') ?>" class="d-inline">
                        <?= csrf_input() ?>
                        <input type="hidden" name="tipo" value="item"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                        <input type="hidden" name="dir" value="up">
                        <button class="btn btn-outline-secondary" title="Mover para cima" <?= $isFirstIt ? 'disabled style="opacity:.35; pointer-events:none;"' : '' ?>><i class="fa fa-chevron-up"></i></button>
                      </form>
                      <form method="post" action="<?= url('menu.reordenar') ?>" class="d-inline">
                        <?= csrf_input() ?>
                        <input type="hidden" name="tipo" value="item"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                        <input type="hidden" name="dir" value="down">
                        <button class="btn btn-outline-secondary" title="Mover para baixo" <?= $isLastIt ? 'disabled style="opacity:.35; pointer-events:none;"' : '' ?>><i class="fa fa-chevron-down"></i></button>
                      </form>
                      <form method="post" action="<?= url('menu.excluir') ?>" class="d-inline" onsubmit="return confirm('Excluir item?')">
                        <?= csrf_input() ?>
                        <input type="hidden" name="tipo" value="item"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                        <input type="hidden" name="menu_id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="submenu_id" value="<?= (int)$sm['id'] ?>">
                        <button class="btn btn-outline-danger" title="Excluir"><i class="fa fa-trash"></i></button>
                      </form>
                    </span>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            </li>
            <?php endforeach; ?>
          </ul>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<!-- Modal formulÃ¡rio (menu/submenu/item) -->
<div class="modal fade" id="modalForm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= url('menu.salvar') ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="tipo" id="f-tipo">
        <input type="hidden" name="id" id="f-id">
        <input type="hidden" name="menu_id" id="f-menu-id">
        <input type="hidden" name="submenu_id" id="f-submenu-id">
        <div class="modal-header">
          <h5 class="modal-title" id="modalFormTitulo">Editar</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div id="modalFormAlert" class="alert alert-danger d-none" role="alert"></div>
          <div class="mb-2">
            <label class="form-label">Nome</label>
            <input type="text" class="form-control" name="nome" id="f-nome" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Ãcone</label>
            <input type="hidden" name="icone" id="f-icone">
            <div id="iconGrid" class="icon-grid"></div>
          </div>
          <div class="mb-2" id="g-rota">
            <label class="form-label">Rota/AÃ§Ã£o</label>
            <input type="text" class="form-control" name="rota_acao" id="f-rota" placeholder="ex.: usuario.listar">
          </div>
          
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="ativo" id="f-ativo" checked>
            <label class="form-check-label" for="f-ativo">Ativo</label>
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

<!-- Modal permissÃµes do item -->
<div class="modal fade" id="modalPerm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= url('menu.salvar') ?>">
        <?= csrf_input() ?>
        <input type="hidden" name="tipo" value="perfis_item">
        <input type="hidden" name="item_id" id="p-item-id">
        <input type="hidden" name="menu_id" id="p-menu-id" value="0">
        <input type="hidden" name="submenu_id" id="p-submenu-id" value="0">
        <div class="modal-header">
          <h5 class="modal-title">PermissÃµes do item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <?php foreach ($perfis as $p): ?>
              <div class="col-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="perfil_ids[]" value="<?= (int)$p['id'] ?>" id="perm-<?= (int)$p['id'] ?>">
                  <label class="form-check-label" for="perm-<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="text-muted small mt-2">Conceder acesso a um item permite aÃ§Ãµes do mesmo prefixo (ex.: menu.*).</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Acesso</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Catálogo de ícones (Font Awesome Free) para seleção
const ICONES = [
  'fa-house','fa-bars','fa-sitemap','fa-list','fa-folder','fa-folder-open','fa-file','fa-circle','fa-square',
  'fa-user','fa-users','fa-id-badge','fa-user-gear','fa-user-shield','fa-user-plus','fa-user-pen',
  'fa-gear','fa-screwdriver-wrench','fa-wrench','fa-cog','fa-database','fa-server','fa-plug',
  'fa-chart-line','fa-chart-column','fa-clock','fa-calendar','fa-calendar-days','fa-bell','fa-envelope',
  'fa-phone','fa-map','fa-location-dot','fa-building','fa-briefcase','fa-box','fa-boxes-stacked','fa-truck',
  'fa-credit-card','fa-money-bill','fa-tag','fa-tags','fa-receipt','fa-print','fa-download','fa-upload',
  'fa-check','fa-xmark','fa-plus','fa-pen','fa-trash'
];

function renderIconPicker(selected, tipo){
  const grid = document.getElementById('iconGrid');
  if (!grid) return;
  grid.innerHTML = '';
  const setSelected = (cls) => {
    document.getElementById('f-icone').value = cls;
    grid.querySelectorAll('.icon-cell').forEach(el => el.classList.toggle('active', el.dataset.icon===cls));
  };
  const defByTipo = tipo==='menu' ? 'fa-bars' : (tipo==='submenu' ? 'fa-folder-open' : 'fa-circle');
  const atual = (selected && selected.trim()) ? selected.trim() : defByTipo;
  ICONES.forEach(ic => {
    const cell = document.createElement('div');
    cell.className = 'icon-cell' + (ic===atual ? ' active' : '');
    cell.dataset.icon = ic;
    cell.innerHTML = `<i class="fa ${ic}"></i>`;
    cell.title = ic;
    cell.addEventListener('click', () => setSelected(ic));
    grid.appendChild(cell);
  });
  setSelected(atual);
}

// Validação inline do formulário do modal (mensagens dentro do próprio modal)
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#modalForm form');
  const alertBox = document.getElementById('modalFormAlert');
  // Corrige rótulos com acentuação independente de encoding
  try {
    const iconGrid = document.getElementById('iconGrid');
    const iconContainer = iconGrid ? iconGrid.parentElement : null;
    const iconLabel = iconContainer ? iconContainer.querySelector('label.form-label') : null;
    if (iconLabel) { iconLabel.innerHTML = '&Iacute;cone'; }
    const rotaLabel = document.querySelector('#g-rota label.form-label');
    if (rotaLabel) { rotaLabel.innerHTML = 'Rota/A&ccedil;&atilde;o'; }
  } catch(_) {}
  if (form) {
    form.addEventListener('submit', (e) => {
      alertBox.classList.add('d-none'); alertBox.textContent = '';
      const tipo = (document.getElementById('f-tipo').value||'').toLowerCase();
      const nome = document.getElementById('f-nome').value.trim();
      const rota = document.getElementById('f-rota') ? document.getElementById('f-rota').value.trim() : '';
      if (nome === '') {
        e.preventDefault(); alertBox.textContent = 'Informe o nome.'; alertBox.classList.remove('d-none'); return;
      }
      if (tipo === 'item' && rota === '') {
        e.preventDefault(); alertBox.textContent = 'Informe a rota do item.'; alertBox.classList.remove('d-none'); return;
      }
      const ic = document.getElementById('f-icone').value.trim();
      if (ic === '') {
        // força seleção pelo default do tipo
        const def = tipo==='menu' ? 'fa-bars' : (tipo==='submenu' ? 'fa-folder-open' : 'fa-circle');
        document.getElementById('f-icone').value = def;
      }
    });
  }
});
// Dados de permissÃµes prÃ©-carregados: item_id => [perfil_ids]
const PERMISSOES = <?= json_encode($permissoesMap ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

// Ordena visualmente itens e submenus na raiz conforme data-ordem (intercalado)
document.querySelectorAll('li.branch[data-tipo="menu"] > ul').forEach(ul => {
  const children = Array.from(ul.children);
  children.sort((a,b) => {
    const ao = parseInt(a.dataset.ordem||'0',10) || 0;
    const bo = parseInt(b.dataset.ordem||'0',10) || 0;
    if (ao !== bo) return ao - bo;
    const at = a.dataset.tipo || '';
    const bt = b.dataset.tipo || '';
    if (at !== bt) return at === 'submenu' ? -1 : 1; // submenus antes, como tie-break
    const ai = parseInt(a.dataset.id||'0',10) || 0;
    const bi = parseInt(b.dataset.id||'0',10) || 0;
    return ai - bi;
  });
  children.forEach(li => ul.appendChild(li));
});

// Marca visualmente o Ãºltimo item movido (armazenado em localStorage)
(function(){
  const lastItemId = parseInt(localStorage.getItem('menuLastMovedItemId')||'0', 10) || 0;
  if (lastItemId > 0) {
    const li = document.querySelector('li[data-tipo="item"][data-id="'+lastItemId+'"]');
    if (li) li.classList.add('is-moved');
  }
  const lastSubId = parseInt(localStorage.getItem('menuLastMovedSubmenuId')||'0', 10) || 0;
  if (lastSubId > 0) {
    const li = document.querySelector('li[data-tipo="submenu"][data-id="'+lastSubId+'"]');
    if (li) li.classList.add('is-moved');
  }
  const lastMenuId = parseInt(localStorage.getItem('menuLastMovedMenuId')||'0', 10) || 0;
  if (lastMenuId > 0) {
    const li = document.querySelector('li[data-tipo="menu"][data-id="'+lastMenuId+'"]');
    if (li) li.classList.add('is-moved');
  }
})();

// Antes de submeter formulÃ¡rios de reordenar por setas, registra o item movido
document.querySelectorAll('form[action*="acao=menu.reordenar"], form[action*="acao=menu.reordenar_nivel"]').forEach(f => {
  f.addEventListener('submit', () => {
    const tipo = (f.querySelector('input[name="tipo"]')?.value||'').toLowerCase();
    const id = parseInt(f.querySelector('input[name="id"]')?.value||'0', 10) || 0;
    if (tipo === 'item' && id > 0) {
      localStorage.setItem('menuLastMovedItemId', String(id));
    } else if (tipo === 'submenu' && id > 0) {
      localStorage.setItem('menuLastMovedSubmenuId', String(id));
    } else if (tipo === 'menu' && id > 0) {
      localStorage.setItem('menuLastMovedMenuId', String(id));
    }
  });
});

// Ao passar o mouse em outra linha, remove a tarja do Ãºltimo movido
document.querySelectorAll('li[data-tipo="item"] > .node, li[data-tipo="submenu"] > .node, li[data-tipo=\"menu\"] > .node').forEach(node => {
  node.addEventListener('mouseenter', () => {
    const moved = document.querySelector('li.is-moved');
    if (moved && moved !== node.parentElement) {
      moved.classList.remove('is-moved');
      localStorage.removeItem('menuLastMovedItemId');
      localStorage.removeItem('menuLastMovedSubmenuId');
      localStorage.removeItem('menuLastMovedMenuId');
    }
  });
});

// Expandir/retrair clicando no nome ou no Ã­cone principal
document.querySelectorAll('#menu-tree .branch > .node .name, #menu-tree .branch > .node > i:first-of-type').forEach(el => {
  el.style.cursor = 'pointer';
  el.addEventListener('click', () => el.closest('.branch').classList.toggle('expanded'));
});

// AÃ§Ãµes de criaÃ§Ã£o
document.querySelector('[data-action="novo-menu"]').addEventListener('click', () => abrirForm({tipo:'menu'}));
document.querySelectorAll('[data-action="novo-submenu"]').forEach(b => b.addEventListener('click', () => {
  abrirForm({tipo:'submenu', menu_id:b.dataset.menuId});
}));
document.querySelectorAll('[data-action="novo-item"]').forEach(b => b.addEventListener('click', () => {
  abrirForm({tipo:'item', menu_id:b.dataset.menuId, submenu_id:b.dataset.submenuId||0});
}));

// AÃ§Ãµes de ediÃ§Ã£o
document.querySelectorAll('[data-action^="editar-"]').forEach(b => b.addEventListener('click', () => {
  const tipo = b.dataset.action.replace('editar-','');
  abrirForm({
    tipo,
    id: b.dataset.id,
    menu_id: b.dataset.menuId || 0,
    submenu_id: b.dataset.submenuId || 0,
    nome: b.dataset.nome || '',
    icone: b.dataset.icone || '',
    rota: b.dataset.rota || '',
    ordem: b.dataset.ordem || 0,
    ativo: (b.dataset.ativo||'1') === '1'
  });
}));

// PermissÃµes
document.querySelectorAll('[data-action="permissoes-item"]').forEach(b => b.addEventListener('click', () => {
  const id = parseInt(b.dataset.itemId,10)||0;
  document.getElementById('p-item-id').value = id;
  // Reset
  document.querySelectorAll('#modalPerm input[type="checkbox"]').forEach(c => c.checked = false);
  (PERMISSOES[id]||[]).forEach(pid => {
    const el = document.getElementById('perm-'+pid);
    if (el) el.checked = true;
  });
  new bootstrap.Modal(document.getElementById('modalPerm')).show();
}));

// Drag & drop de itens (mover entre submenus do mesmo menu ou para raiz)
let DRAG = null;
document.querySelectorAll('li[data-tipo="item"]').forEach(li => {
  li.addEventListener('dragstart', e => {
    DRAG = {
      tipo: 'item',
      id: parseInt(li.dataset.id,10),
      menuId: parseInt(li.dataset.menuId,10),
      submenuId: parseInt(li.dataset.submenuId,10) || 0
    };
    // Prevent bubbling to parent submenu (also draggable)
    try { e.stopPropagation(); } catch(_) {}
    e.dataTransfer.effectAllowed = 'move';
    try { e.dataTransfer.setData('text/plain', String(DRAG.id)); } catch(_) {}
  });
  li.addEventListener('dragend', () => { DRAG = null; });
});

// Drag de submenus (mover entre menus)
document.querySelectorAll('li.branch[data-tipo="submenu"]').forEach(li => {
  li.addEventListener('dragstart', e => {
    // Ignore if drag started from an inner item
    if (e.target && e.target.closest && e.target.closest('li[data-tipo="item"]')) { return; }
    DRAG = {
      tipo: 'submenu',
      id: parseInt(li.dataset.id,10),
      menuId: parseInt(li.dataset.menuId,10)
    };
    try { e.stopPropagation(); } catch(_) {}
    e.dataTransfer.effectAllowed = 'move';
    try { e.dataTransfer.setData('text/plain', String(DRAG.id)); } catch(_) {}
  });
  li.addEventListener('dragend', () => { DRAG = null; });
});

// Permitir drop tambÃ©m no cabeÃ§alho (node) do menu para enviar Ã  raiz
document.querySelectorAll('li.branch[data-tipo="menu"] > .node').forEach(node => {
  node.addEventListener('dragover', e => {
    if (!DRAG) return; const targetMenuId = parseInt(node.parentElement.dataset.menuId,10);
    e.preventDefault(); e.stopPropagation(); e.dataTransfer.dropEffect = 'move'; node.classList.add('drop-allow'); node.classList.remove('drop-deny');
  });
  node.addEventListener('drop', e => {
    if (!DRAG) return; e.preventDefault(); e.stopPropagation(); const targetMenuId = parseInt(node.parentElement.dataset.menuId,10);
    node.classList.remove('drop-allow','drop-deny');
    if (DRAG.tipo === 'submenu') enviarMovimentoSubmenu(DRAG.id, targetMenuId); else enviarMovimento(DRAG.id, targetMenuId, 0);
  });
  node.addEventListener('dragleave', ()=> node.classList.remove('drop-allow','drop-deny'));
});

// Permitir drop no cabeÃ§alho do submenu para enviar ao submenu mesmo se estiver vazio
document.querySelectorAll('li.branch[data-tipo="submenu"] > .node').forEach(node => {
  node.addEventListener('dragover', e => {
    if (!DRAG || DRAG.tipo !== 'item') return; const targetMenuId = parseInt(node.parentElement.dataset.menuId,10);
    e.preventDefault(); e.stopPropagation(); e.dataTransfer.dropEffect = 'move'; node.classList.add('drop-allow'); node.classList.remove('drop-deny');
  });
  node.addEventListener('drop', e => {
    if (!DRAG || DRAG.tipo !== 'item') return; e.preventDefault(); e.stopPropagation(); const targetMenuId = parseInt(node.parentElement.dataset.menuId,10);
    node.classList.remove('drop-allow','drop-deny');
    const sid = parseInt(node.parentElement.dataset.id,10);
    enviarMovimento(DRAG.id, targetMenuId, sid);
  });
  node.addEventListener('dragleave', ()=> node.classList.remove('drop-allow','drop-deny'));
});

// Alvos de drop: listas de itens de cada menu (raiz) e de cada submenu
document.querySelectorAll('li.branch[data-tipo="menu"] > ul').forEach(ul => {
  ul.addEventListener('dragover', e => {
    if (!DRAG) return; const targetMenuId = parseInt(ul.parentElement.dataset.menuId,10);
    // SÃ³ aceita se for mesmo menu (drop na raiz)
    if (DRAG.menuId === targetMenuId) { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; ul.classList.add('drop-allow'); ul.classList.remove('drop-deny'); }
    else { e.dataTransfer.dropEffect = 'none'; ul.classList.add('drop-deny'); ul.classList.remove('drop-allow'); }
  });
  ul.addEventListener('drop', e => {
    if (!DRAG) return; const targetMenuId = parseInt(ul.parentElement.dataset.menuId,10);
    if (DRAG.menuId !== targetMenuId) return;
    enviarMovimento(DRAG.id, targetMenuId, 0);
    ul.classList.remove('drop-allow','drop-deny');
  });
  ul.addEventListener('dragleave', ()=> ul.classList.remove('drop-allow','drop-deny'));
});
document.querySelectorAll('li.branch[data-tipo="submenu"] > ul').forEach(ul => {
  ul.addEventListener('dragover', e => {
    if (!DRAG) return; const targetMenuId = parseInt(ul.parentElement.dataset.menuId,10);
    e.preventDefault(); e.stopPropagation(); e.dataTransfer.dropEffect = 'move'; ul.classList.add('drop-allow'); ul.classList.remove('drop-deny');
  });
  ul.addEventListener('drop', e => {
    if (!DRAG) return; e.preventDefault(); e.stopPropagation(); const targetMenuId = parseInt(ul.parentElement.dataset.menuId,10);
    const sid = parseInt(ul.parentElement.dataset.id,10);
    enviarMovimento(DRAG.id, targetMenuId, sid);
    ul.classList.remove('drop-allow','drop-deny');
  });
  ul.addEventListener('dragleave', ()=> ul.classList.remove('drop-allow','drop-deny'));
});

function enviarMovimento(itemId, menuId, submenuId){
  const fd = new FormData();
  fd.append('csrf_token', '<?= App\Lib\CSRF\token() ?>');
  fd.append('item_id', itemId);
  fd.append('menu_id', menuId);
  fd.append('submenu_id', submenuId);
  try { localStorage.setItem('menuLastMovedItemId', String(itemId)); } catch(_) {}
  fetch('<?= url('menu.mover_item') ?>', { method:'POST', body: fd, credentials: 'same-origin' })
    .then(async r=>{ try { return {status:r.status, body: await r.json()}; } catch(e){ return {status:r.status, body:null}; } })
    .then(({status, body})=>{ if(status===200 && body && body.ok){ location.reload(); } else { console.error('Falha mover_item', status, body); alert('Não foi possíel mover o item.'); } })
    .catch((e)=>{ console.error(e); alert('Falha de rede ao mover item.'); });
}

// Novo: mover submenu entre menus + reforço de DnD para aceitar cross-menu
function enviarMovimentoSubmenu(submenuId, menuId){
  const fd = new FormData();
  fd.append('csrf_token', '<?= App\Lib\CSRF\token() ?>');
  fd.append('submenu_id', submenuId);
  fd.append('menu_id', menuId);
  fetch('<?= url('menu.mover_submenu') ?>', { method:'POST', body: fd, credentials: 'same-origin' })
    .then(async r=>{ try { return {status:r.status, body: await r.json()}; } catch(e){ return {status:r.status, body:null}; } })
    .then(({status, body})=>{ if(status===200 && body && body.ok){ location.reload(); } else { console.error('Falha mover_submenu', status, body); alert('Falha ao mover o submenu.'); } })
    .catch((e)=>{ console.error(e); alert('Falha de rede ao mover submenu.'); });
}

// Nota: listeners acima já tratam cross-menu. Evitar captura no ancestral para não ofuscar o drop em submenus.

function abrirForm(opts){
  const t = (opts.tipo||'').toLowerCase();
  document.getElementById('f-tipo').value = t;
  document.getElementById('f-id').value = opts.id||'';
  document.getElementById('f-menu-id').value = opts.menu_id||0;
  document.getElementById('f-submenu-id').value = opts.submenu_id||0;
  document.getElementById('f-nome').value = opts.nome||'';
  // preparar grade de ícones e seleção atual
  renderIconPicker(opts.icone||'', t);
  // ordem é automática; nenhum campo visível/alterável
  document.getElementById('f-ativo').checked = opts.id ? !!opts.ativo : true;
  const gRota = document.getElementById('g-rota');
  if (t === 'item') { gRota.style.display='block'; document.getElementById('f-rota').value = opts.rota||''; }
  else { gRota.style.display='none'; document.getElementById('f-rota').value=''; }
  const titulo = t==='menu' ? 'Menu' : (t==='submenu' ? 'Submenu' : 'Item');
  document.getElementById('modalFormTitulo').innerText = (opts.id? 'Editar ' : 'Novo ') + titulo;
  const alertBox = document.getElementById('modalFormAlert');
  if (alertBox) { alertBox.classList.add('d-none'); alertBox.textContent = ''; }
  new bootstrap.Modal(document.getElementById('modalForm')).show();
}
</script>

<?php include __DIR__ . '/../partials/rodape.php'; ?>
