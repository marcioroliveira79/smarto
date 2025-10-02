<?php
declare(strict_types=1);

namespace App\Modelos\Usuario;

use App\DB;
use PDO;

function listar(): array {
    $pdo = DB\pdo();
    $st = $pdo->query('SELECT u.id, u.nome, u.email, u.telefone, u.ativo, u.criado_em, p.nome AS perfil
                       FROM usuario u JOIN perfil p ON p.id = u.perfil_id
                       ORDER BY u.nome');
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function buscar(int $id): ?array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM usuario WHERE id = :id');
    $st->execute([':id' => $id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function criar(string $nome, string $email, int $perfil_id, bool $ativo, string $senha, string $telefone): int {
    $pdo = DB\pdo();
    $hash = password_hash($senha, PASSWORD_BCRYPT);
    $st = $pdo->prepare('INSERT INTO usuario (nome, email, telefone, senha_hash, ativo, perfil_id)
                         VALUES (:n, :e, :t, :h, :a, :p) RETURNING id');
    $st->bindValue(':n', $nome, PDO::PARAM_STR);
    $st->bindValue(':e', $email, PDO::PARAM_STR);
    $st->bindValue(':t', $telefone, PDO::PARAM_STR);
    $st->bindValue(':h', $hash, PDO::PARAM_STR);
    $st->bindValue(':a', $ativo, PDO::PARAM_BOOL);
    $st->bindValue(':p', $perfil_id, PDO::PARAM_INT);
    $st->execute();
    return (int)$st->fetchColumn();
}

function atualizar(int $id, string $nome, string $email, string $telefone, int $perfil_id, bool $ativo, ?string $senha): bool {
    $pdo = DB\pdo();
    if ($senha !== null && $senha !== '') {
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $sql = 'UPDATE usuario SET nome=:n, email=:e, telefone=:t, perfil_id=:p, ativo=:a, senha_hash=:h, senha_trocada_em = now(), atualizado_em = now() WHERE id=:id';
        $st = $pdo->prepare($sql);
        $st->bindValue(':h', $hash, PDO::PARAM_STR);
    } else {
        $sql = 'UPDATE usuario SET nome=:n, email=:e, telefone=:t, perfil_id=:p, ativo=:a, atualizado_em = now() WHERE id=:id';
        $st = $pdo->prepare($sql);
    }
    $st->bindValue(':n', $nome, PDO::PARAM_STR);
    $st->bindValue(':e', $email, PDO::PARAM_STR);
    $st->bindValue(':t', $telefone, PDO::PARAM_STR);
    $st->bindValue(':p', $perfil_id, PDO::PARAM_INT);
    $st->bindValue(':a', $ativo, PDO::PARAM_BOOL);
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    return $st->execute();
}

function excluir(int $id): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare('DELETE FROM usuario WHERE id = :id');
    return $st->execute([':id' => $id]);
}

function telefone_existe(string $telefone, ?int $ignoraId = null): bool {
    $pdo = DB\pdo();
    if ($ignoraId) {
        $st = $pdo->prepare('SELECT 1 FROM usuario WHERE telefone = :t AND id <> :id LIMIT 1');
        $st->execute([':t' => $telefone, ':id' => $ignoraId]);
    } else {
        $st = $pdo->prepare('SELECT 1 FROM usuario WHERE telefone = :t LIMIT 1');
        $st->execute([':t' => $telefone]);
    }
    return (bool)$st->fetchColumn();
}

function atualizar_telefone_e_senha(int $id, string $telefone, ?string $senha): bool {
    $pdo = DB\pdo();
    if ($senha !== null && $senha !== '') {
        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $sql = 'UPDATE usuario SET telefone=:t, senha_hash=:h, senha_trocada_em = now(), atualizado_em = now() WHERE id=:id';
        $params = [':t' => $telefone, ':h' => $hash, ':id' => $id];
    } else {
        $sql = 'UPDATE usuario SET telefone=:t, atualizado_em = now() WHERE id=:id';
        $params = [':t' => $telefone, ':id' => $id];
    }
    $st = $pdo->prepare($sql);
    return $st->execute($params);
}
