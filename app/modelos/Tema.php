<?php
declare(strict_types=1);

namespace App\Modelos\Tema;

use App\DB;
use PDO;

function listar(): array {
    $pdo = DB\pdo();
    $st = $pdo->query('SELECT * FROM tema ORDER BY nome');
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function buscar(int $id): ?array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM tema WHERE id = :id');
    $st->execute([':id' => $id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function criar(string $nome, array $cores): int {
    $pdo = DB\pdo();
    $st = $pdo->prepare('INSERT INTO tema (nome, cores, criado_em, atualizado_em) VALUES (:n, :c::jsonb, now(), now()) RETURNING id');
    $st->execute([':n' => $nome, ':c' => json_encode($cores, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
    return (int)$st->fetchColumn();
}

function atualizar(int $id, string $nome, array $cores): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare('UPDATE tema SET nome=:n, cores=:c::jsonb, atualizado_em=now() WHERE id=:id');
    return $st->execute([':n'=>$nome, ':c'=>json_encode($cores, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ':id'=>$id]);
}

function excluir(int $id): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare('DELETE FROM tema WHERE id = :id');
    return $st->execute([':id'=>$id]);
}

