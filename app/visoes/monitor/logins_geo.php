<?php
declare(strict_types=1);

use function App\Lib\url;

// $usuariosLogins: lista com ['usuario_id','nome','email','logins'=>[]]
include __DIR__ . '/../partials/cabecalho.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>.map-container{height:360px}.cursor-pointer{cursor:pointer}</style>

<div class="card shadow-sm mt-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fa fa-map-location-dot me-2"></i>Logins com Localização</div>
    <div>
      <a href="<?= url('monitor.online') ?>" class="btn btn-outline-secondary btn-sm"><i class="fa fa-wifi me-1"></i>Online</a>
    </div>
  </div>
  <div class="card-body">
    <?php if (empty($usuariosLogins)): ?>
      <div class="alert alert-info">Nenhum registro de localização de login encontrado.</div>
    <?php else: ?>
      <div class="accordion" id="acc-logins">
        <?php foreach ($usuariosLogins as $i => $u): $uid = (int)$u['usuario_id']; $accId = 'u'.$uid; ?>
        <div class="accordion-item">
          <h2 class="accordion-header" id="h-<?= $accId ?>">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-<?= $accId ?>" aria-expanded="false" aria-controls="c-<?= $accId ?>">
              <i class="fa fa-user me-2"></i><?= htmlspecialchars((string)$u['nome']) ?> <small class="text-muted ms-2"><?= htmlspecialchars((string)$u['email']) ?></small>
            </button>
          </h2>
          <div id="c-<?= $accId ?>" class="accordion-collapse collapse" aria-labelledby="h-<?= $accId ?>" data-bs-parent="#acc-logins">
            <div class="accordion-body">
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Data/Hora</th>
                      <th>Fonte</th>
                      <th>Permissão</th>
                      <th>Coordenadas</th>
                      <th>Precisão (m)</th>
                      <th>IP</th>
                      <th>Ação</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($u['logins'] as $row): ?>
                    <tr>
                      <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime((string)$row['login_em']))) ?></td>
                      <td><span class="badge bg-secondary"><?= htmlspecialchars((string)$row['fonte']) ?></span></td>
                      <td>
                        <?php $perm = strtolower((string)$row['permissao']); $cls = $perm==='granted'?'success':($perm==='denied'?'danger':'warning'); ?>
                        <span class="badge bg-<?= $cls ?>"><?= htmlspecialchars($perm) ?></span>
                      </td>
                      <td>
                        <?php if ($row['latitude'] !== null && $row['longitude'] !== null): ?>
                          <?= htmlspecialchars((string)$row['latitude']) ?>, <?= htmlspecialchars((string)$row['longitude']) ?>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars((string)($row['precisao_m'] ?? '')) ?></td>
                      <td><code><?= htmlspecialchars((string)($row['ip'] ?? '')) ?></code></td>
                      <td>
                        <?php if ($row['latitude'] !== null && $row['longitude'] !== null): ?>
                          <button class="btn btn-sm btn-outline-primary btn-map" data-lat="<?= htmlspecialchars((string)$row['latitude']) ?>" data-lon="<?= htmlspecialchars((string)$row['longitude']) ?>" data-nome="<?= htmlspecialchars((string)$u['nome']) ?>" title="Ver no mapa">
                            <i class="fa fa-map"></i>
                          </button>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="card-footer">
    <a href="<?= App\Lib\url('dashboard.inicio') ?>" class="btn btn-outline-secondary app-link"><i class="fa fa-arrow-left me-1"></i>Voltar</a>
  </div>
</div>

<!-- Modal Mapa -->
<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-map-location-dot me-2"></i><span id="mapTitle">Localização</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div id="map" class="map-container"></div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../partials/rodape.php'; ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function(){
  let map, marker, modal;
  const modalEl = document.getElementById('mapModal');
  const mapTitle = document.getElementById('mapTitle');

  function ensureMap(){
    if (!map){
      map = L.map('map');
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    }
  }

  function showOnMap(lat, lon, title){
    ensureMap();
    const center = [parseFloat(lat), parseFloat(lon)];
    map.setView(center, 15);
    if (marker){ marker.remove(); }
    marker = L.marker(center).addTo(map);
    marker.bindPopup(title || 'Localização').openPopup();
    setTimeout(function(){ map.invalidateSize(); }, 250);
  }

  document.addEventListener('click', function(ev){
    const btn = ev.target.closest('.btn-map');
    if (!btn) return;
    const lat = btn.getAttribute('data-lat');
    const lon = btn.getAttribute('data-lon');
    const nome = btn.getAttribute('data-nome');
    if (!modal){ modal = new bootstrap.Modal(modalEl); }
    mapTitle.textContent = 'Localização de ' + (nome || 'usuário');
    modal.show();
    setTimeout(function(){ showOnMap(lat, lon, nome); }, 150);
  });
})();
</script>

