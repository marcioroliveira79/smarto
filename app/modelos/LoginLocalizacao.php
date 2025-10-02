<?php
declare(strict_types=1);

namespace App\Modelos\LoginLocalizacao;

use App\DB;
use PDO;

function registrar(array $dados): bool {
    $pdo = DB\pdo();
    $sql = 'INSERT INTO login_localizacao
            (usuario_id, sessao_id, login_em, fonte, permissao, latitude, longitude, precisao_m, ip, user_agent, capturado_em, observacoes)
            VALUES (:usuario_id, :sessao_id, :login_em, :fonte, :permissao, :lat, :lon, :precisao, :ip, :ua, :capturado_em, :obs)';
    $st = $pdo->prepare($sql);
    $st->bindValue(':usuario_id', (int)$dados['usuario_id'], PDO::PARAM_INT);
    $st->bindValue(':sessao_id', (string)($dados['sessao_id'] ?? ''), PDO::PARAM_STR);
    $st->bindValue(':login_em', $dados['login_em'] ?? date('c'));
    $st->bindValue(':fonte', (string)($dados['fonte'] ?? 'device'), PDO::PARAM_STR);
    $st->bindValue(':permissao', (string)($dados['permissao'] ?? 'granted'), PDO::PARAM_STR);
    $st->bindValue(':lat', isset($dados['latitude']) ? (string)$dados['latitude'] : null, $dados['latitude'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $st->bindValue(':lon', isset($dados['longitude']) ? (string)$dados['longitude'] : null, $dados['longitude'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $st->bindValue(':precisao', isset($dados['precisao_m']) ? (string)$dados['precisao_m'] : null, $dados['precisao_m'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $st->bindValue(':ip', (string)($dados['ip'] ?? ''), PDO::PARAM_STR);
    $st->bindValue(':ua', (string)($dados['user_agent'] ?? ''), PDO::PARAM_STR);
    $st->bindValue(':capturado_em', $dados['capturado_em'] ?? date('c'));
    $st->bindValue(':obs', (string)($dados['observacoes'] ?? ''));
    return $st->execute();
}

