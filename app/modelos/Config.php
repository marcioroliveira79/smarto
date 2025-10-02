<?php
declare(strict_types=1);

namespace App\Modelos\Config;

use App\DB;
use PDO;

function listar(): array {
    $pdo = DB\pdo();
    $sql = 'SELECT c.*, uc.nome AS criado_por_nome, ua.nome AS atualizado_por_nome
            FROM config_sistema c
            LEFT JOIN usuario uc ON uc.id = c.criado_por
            LEFT JOIN usuario ua ON ua.id = c.atualizado_por
            ORDER BY c.processo, c.chave';
    $st = $pdo->query($sql);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function buscar(int $id): ?array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM config_sistema WHERE id = :id');
    $st->execute([':id' => $id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function obter(string $processo, string $chave): ?string {
    $pdo = DB\pdo();
    // Busca case-insensitive para facilitar uso
    $st = $pdo->prepare('SELECT valor FROM config_sistema WHERE lower(processo) = lower(:p) AND lower(chave) = lower(:c)');
    $st->execute([':p' => trim($processo), ':c' => trim($chave)]);
    $v = $st->fetchColumn();
    return $v === false ? null : (string)$v;
}

function salvar(?int $id, string $processo, string $chave, ?string $valor, string $tipo, ?string $descricao, ?int $usuarioId = null): int {
    $pdo = DB\pdo();
    if ($id) {
        $st = $pdo->prepare('UPDATE config_sistema SET processo=:p, chave=:c, valor=:v, tipo=:t, descricao=:d, atualizado_em=now(), atualizado_por=:u WHERE id=:id');
        $st->execute([':p'=>$processo, ':c'=>$chave, ':v'=>$valor, ':t'=>$tipo, ':d'=>$descricao, ':u'=>$usuarioId, ':id'=>$id]);
        return $id;
    }
    $st = $pdo->prepare('INSERT INTO config_sistema (processo, chave, valor, tipo, descricao, criado_em, criado_por, atualizado_por)
                         VALUES (:p,:c,:v,:t,:d, now(), :u, :u) RETURNING id');
    $st->execute([':p'=>$processo, ':c'=>$chave, ':v'=>$valor, ':t'=>$tipo, ':d'=>$descricao, ':u'=>$usuarioId]);
    return (int)$st->fetchColumn();
}

function excluir(int $id): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare('DELETE FROM config_sistema WHERE id = :id');
    return $st->execute([':id'=>$id]);
}
