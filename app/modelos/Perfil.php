<?php
declare(strict_types=1);

namespace App\Modelos\Perfil;

use App\DB;
use PDO;

function listar(): array {
    $pdo = DB\pdo();
    $st = $pdo->query('SELECT * FROM perfil ORDER BY nome');
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function listar_ativos(): array {
    $pdo = DB\pdo();
    $st = $pdo->query('SELECT * FROM perfil WHERE ativo = true ORDER BY nome');
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function buscar(int $id): ?array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM perfil WHERE id = :id');
    $st->execute([':id' => $id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function criar(string $nome, string $descricao, bool $ativo): int {
    $pdo = DB\pdo();
    $st = $pdo->prepare('INSERT INTO perfil (nome, descricao, ativo) VALUES (:n, :d, :a) RETURNING id');
    $st->execute([':n' => $nome, ':d' => $descricao, ':a' => $ativo]);
    return (int)$st->fetchColumn();
}

function atualizar(int $id, string $nome, string $descricao, bool $ativo): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare('UPDATE perfil SET nome=:n, descricao=:d, ativo=:a WHERE id=:id');
    return $st->execute([':n' => $nome, ':d' => $descricao, ':a' => $ativo, ':id' => $id]);
}

function excluir(int $id): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare('DELETE FROM perfil WHERE id = :id');
    return $st->execute([':id' => $id]);
}

