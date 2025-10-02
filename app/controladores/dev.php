<?php
declare(strict_types=1);

use function App\Lib\usuario_logado;
use function App\Lib\set_flash;
use function App\Lib\redirecionar;
use function App\Lib\Migrador\aplicarTudo;

require_once __DIR__ . '/../biblioteca/migrador.php';

function aplicar_sql(): void {
    // Segurança: apenas em ambiente local.
    $env = $_ENV['APP_ENV'] ?? 'local';
    $u = usuario_logado();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocalIp = in_array($ip, ['127.0.0.1', '::1'], true);
    $isAdmin = $u && (strtolower((string)$u['perfil_nome']) === 'administrador');
    if ($env !== 'local' || (!$isAdmin && !$isLocalIp)) {
        http_response_code(403);
        echo 'Ação não permitida.';
        return;
    }

    $base = realpath(__DIR__ . '/..' . '/..');
    $res = aplicarTudo($base);

    include __DIR__ . '/../visoes/dev/aplicar_sql.php';
}
