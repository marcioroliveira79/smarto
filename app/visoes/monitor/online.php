<?php
declare(strict_types=1);

include __DIR__ . '/../partials/cabecalho.php';
?>
<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fa fa-signal me-1"></i>Usuários online</div>
    <div class="text-muted small">Atualiza a cada <?= (int)($refreshSeg ?? 45) ?>s</div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped" id="tbl-online">
        <thead>
          <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Último check-in</th>
            <th>Status</th>
            <th>IP</th>
            <th>Agente</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['nome']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="dt" data-iso="<?= htmlspecialchars($u['ultimo_sinal_iso']) ?>"><?= htmlspecialchars((string)$u['ultimo_sinal']) ?></span></td>
            <td><span class="badge text-bg-success">online</span></td>
            <td><?= htmlspecialchars((string)$u['ip']) ?></td>
            <td class="text-truncate" style="max-width: 360px;" title="<?= htmlspecialchars((string)$u['user_agent']) ?>"><?= htmlspecialchars((string)$u['user_agent']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <a href="<?= App\Lib\url('dashboard.inicio') ?>" class="btn btn-outline-secondary app-link"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
    <div class="text-muted small">
      Parâmetros: janela_online_segundos = <strong><?= (int)($janelaSegundos ?? 120) ?></strong>s,
      tempo_atualiza_dash_monitoramento = <strong><?= (int)($refreshSeg ?? 45) ?></strong>s
    </div>
  </div>
</div>

<script>
  function timeAgo(date){
    const now = new Date();
    const diff = Math.max(0, now - date);
    const s = Math.floor(diff/1000);
    if (s < 60) return s + 's atrás';
    const m = Math.floor(s/60);
    if (m < 60) return m + 'm atrás';
    const h = Math.floor(m/60);
    return h + 'h atrás';
  }
  function renderRows(list){
    const tbody = document.querySelector('#tbl-online tbody');
    tbody.innerHTML = '';
    list.forEach(u => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${escapeHtml(u.nome||'')}</td>
        <td>${escapeHtml(u.email||'')}</td>
        <td><span class="dt" data-iso="${escapeHtml(u.ultimo_sinal_iso||'')}"></span></td>
        <td><span class="badge text-bg-success">online</span></td>
        <td>${escapeHtml(u.ip||'')}</td>
        <td class="text-truncate" style="max-width:360px;" title="${escapeHtml(u.user_agent||'')}">${escapeHtml(u.user_agent||'')}</td>
      `;
      tbody.appendChild(tr);
    });
    document.querySelectorAll('#tbl-online .dt').forEach(el => {
      const d = new Date(el.dataset.iso);
      if (!isNaN(d)) el.textContent = d.toLocaleString() + ' (' + timeAgo(d) + ')';
    });
  }
  function escapeHtml(s){ return (s||'').replace(/[&<>\"]/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"})[c]); }
  async function refresh(){
    try{
      const r = await fetch('<?= App\Lib\url('monitor.online', ['format'=>'json']) ?>' + '&_ts=' + Date.now(), {credentials:'same-origin', cache:'no-store'});
      const j = await r.json();
      if (j && j.ok) renderRows(j.usuarios||[]);
    }catch(e){ console.error(e); }
  }
  // Atualiza primeiro e a cada N segundos (config)
  refresh();
  setInterval(refresh, <?= (int)($refreshSeg ?? 45) * 1000 ?>);
</script>

<?php include __DIR__ . '/../partials/rodape.php'; ?>
