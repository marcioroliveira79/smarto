<?php
namespace App\Lib;

use App\DB;
use App\Config;
use PDO;

function url(string $acao, array $params = []): string {
    $params = array_merge(['acao' => $acao], $params);
    $query = http_build_query($params);
    return Config\base_path() . '/index.php' . ($query ? ('?' . $query) : '');
}

function redirecionar(string $acao, array $params = []): void {
    header('Location: ' . url($acao, $params));
    exit;
}

function sanitize(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function set_flash(string $tipo, string $mensagem): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function usuario_logado(): ?array {
    return $_SESSION['usuario'] ?? null;
}

function require_login(): void {
    if (!usuario_logado()) {
        redirecionar('autenticacao.login');
    }
}

function auditar_acao(string $acao, array $detalhes = []): void {
    try {
        $pdo = DB\pdo();
        $usuarioId = usuario_logado()['id'] ?? null;
        $stmt = $pdo->prepare('INSERT INTO log_auditoria (usuario_id, acao, detalhes, ip, user_agent) VALUES (:uid, :acao, :det, :ip, :ua)');
        $stmt->execute([
            ':uid' => $usuarioId,
            ':acao' => $acao,
            ':det' => json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':ip' => obter_ip(),
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);
    } catch (\Throwable $e) {
        // Não derruba a página por falha de auditoria
    }
}

// Auditoria de logout com de-duplicação em janela curta
function auditar_logout(string $motivo, array $extras = []): void {
    $now = time();
    $lastTs = (int)($_SESSION['__logout_audit_ts'] ?? 0);
    $lastMotivo = (string)($_SESSION['__logout_audit_motivo'] ?? '');
    $cookieTs = isset($_COOKIE['__logout_audit_ts']) ? (int)$_COOKIE['__logout_audit_ts'] : 0;
    $cookieMotivo = (string)($_COOKIE['__logout_audit_motivo'] ?? '');
    if ((($now - $lastTs) < 3 && $lastMotivo === $motivo) || (($now - $cookieTs) < 3 && $cookieMotivo === $motivo)) {
        return; // evita duplicar registros por múltiplas requisições simultâneas
    }
    auditar_acao('logout', array_merge(['motivo' => $motivo], $extras));
    $_SESSION['__logout_audit_ts'] = $now;
    $_SESSION['__logout_audit_motivo'] = $motivo;
    // pequena janela via cookie para concorrência entre requisições
    $path = Config\base_path() ?: '/';
    @setcookie('__logout_audit_ts', (string)$now, [ 'expires' => $now + 10, 'path' => $path, 'samesite' => 'Lax' ]);
    @setcookie('__logout_audit_motivo', $motivo, [ 'expires' => $now + 10, 'path' => $path, 'samesite' => 'Lax' ]);
}

function obter_ip(): string {
    $keys = ['HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) return (string)$_SERVER[$k];
    }
    return '0.0.0.0';
}

function confirmar_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo 'Método não permitido';
        exit;
    }
}
