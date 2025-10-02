<?php
declare(strict_types=1);

namespace App\Modelos\SenhaReset;

use App\DB;
use PDO;

function criar(int $usuario_id, string $token, int $ttlSegundos = 3600): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare("INSERT INTO senha_reset (usuario_id, token, expira_em) VALUES (:u, :t, now() + (:ttl::int) * INTERVAL '1 second')");
    return $st->execute([':u' => $usuario_id, ':t' => $token, ':ttl' => max(60, $ttlSegundos)]);
}

function buscar_por_token(string $token): ?array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM senha_reset WHERE token = :t');
    $st->execute([':t' => $token]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function marcar_usado(string $token): void {
    try {
        $pdo = DB\pdo();
        $st = $pdo->prepare('UPDATE senha_reset SET usado_em = now() WHERE token = :t');
        $st->execute([':t' => $token]);
    } catch (\Throwable $e) { /* noop */ }
}

function invalidar_antigos(int $usuario_id): void {
    try {
        $pdo = DB\pdo();
        $pdo->prepare('DELETE FROM senha_reset WHERE usuario_id = :u OR expira_em < now()')->execute([':u' => $usuario_id]);
    } catch (\Throwable $e) { /* noop */ }
}
