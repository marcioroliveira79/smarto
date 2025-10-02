<?php
declare(strict_types=1);

function ping(): void {
    App\Lib\registrar_presenca();
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'ts' => time()]);
}

function online(): void {
    // Garante que o usuário atual já esteja marcado como online neste acesso
    App\Lib\registrar_presenca();

    // Janela para considerar "online" (segundos), configurable
    require_once __DIR__ . '/../modelos/Config.php';
    $cfgJanela = \App\Modelos\Config\obter('monitoramento', 'janela_online_segundos');
    $janelaSegundos = (int)($_GET['janela'] ?? ($cfgJanela !== null ? (int)$cfgJanela : 120)); // padrão 120s
    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode(['ok' => true, 'usuarios' => coletar_online($janelaSegundos)]);
        return;
    }
    $usuarios = coletar_online($janelaSegundos);
    // Intervalo de atualização do dashboard (segundos)
    $cfgRefresh = \App\Modelos\Config\obter('monitoramento', 'tempo_atualiza_dash_monitoramento');
    $refreshSeg = (int)($cfgRefresh !== null ? (int)$cfgRefresh : 45);
    include __DIR__ . '/../visoes/monitor/online.php';
}

function coletar_online(int $janelaSegundos): array {
    $pdo = App\DB\pdo();
    // Se sistema em manutenção, apenas Administrador deve ser listado
    $manut = \App\Modelos\Config\obter('sistema', 'sistema_em_manutencao');
    $em_manutencao = false;
    if ($manut !== null) { $v=strtolower(trim($manut)); $em_manutencao = in_array($v,['1','true','t','yes','sim','y'], true); }

    if ($em_manutencao) {
        $sql = "SELECT u.id, u.nome, u.email, o.ip, o.user_agent, o.ultimo_sinal
                  FROM usuario_online o
                  JOIN usuario u ON u.id = o.usuario_id
                  JOIN perfil p ON p.id = u.perfil_id
                 WHERE lower(p.nome) = 'administrador'
                   AND o.ultimo_sinal > (now() - CAST(:janela AS interval))
                 ORDER BY o.ultimo_sinal DESC";
        $st = $pdo->prepare($sql);
    } else {
        $st = $pdo->prepare("SELECT u.id, u.nome, u.email, o.ip, o.user_agent, o.ultimo_sinal
                               FROM usuario_online o
                               JOIN usuario u ON u.id = o.usuario_id
                              WHERE o.ultimo_sinal > (now() - CAST(:janela AS interval))
                              ORDER BY o.ultimo_sinal DESC");
    }
    $interval = max(15, $janelaSegundos) . ' seconds';
    $st->execute([':janela' => $interval]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    // Normaliza campos
    foreach ($rows as &$r) {
        $r['status'] = 'online';
        $r['ultimo_sinal_iso'] = date('c', strtotime((string)$r['ultimo_sinal']));
    }
    return $rows;
}

function logins_geo(): void {
    // Lista logins com localização agrupados por usuário
    $pdo = App\DB\pdo();
    $sql = "SELECT u.id AS usuario_id, u.nome, u.email,
                   l.id AS login_id, l.login_em, l.fonte, l.permissao,
                   l.latitude, l.longitude, l.precisao_m, l.ip, l.user_agent, l.capturado_em
              FROM login_localizacao l
              JOIN usuario u ON u.id = l.usuario_id
             ORDER BY lower(u.nome) ASC, l.login_em DESC";
    $st = $pdo->query($sql);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Agrupar por usuário
    $porUsuario = [];
    foreach ($rows as $r) {
        $uid = (int)$r['usuario_id'];
        if (!isset($porUsuario[$uid])) {
            $porUsuario[$uid] = [
                'usuario_id' => $uid,
                'nome' => (string)$r['nome'],
                'email' => (string)$r['email'],
                'logins' => [],
            ];
        }
        $porUsuario[$uid]['logins'][] = $r;
    }
    // Reindexar em lista para a view
    $usuariosLogins = array_values($porUsuario);

    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/monitor/logins_geo.php';
}
