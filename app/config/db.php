<?php
declare(strict_types=1);

namespace App\DB;

use PDO;
use PDOException;

function pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = (int)($_ENV['DB_PORT'] ?? '5432');
    $db   = $_ENV['DB_NAME'] ?? 'smarto';
    $user = $_ENV['DB_USER'] ?? 'smarto_adm';
    $pass = $_ENV['DB_SENHA'] ?? '';
    $schema = $_ENV['DB_SCHEMA'] ?? 'adm';

    $dsn = "pgsql:host={$host};port={$port};dbname={$db};";
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Define schema padrão da sessão
        $pdo->exec("SET search_path TO \"{$schema}\", public");
    } catch (PDOException $e) {
        http_response_code(500);
        include __DIR__ . '/../visoes/erros/db_conexao.php';
        exit;
    }
    return $pdo;
}

