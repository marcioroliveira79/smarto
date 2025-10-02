<?php
declare(strict_types=1);

// Carrega variáveis do arquivo .env simples (sem dependências externas)
// Formato: CHAVE=valor

$envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key === '') continue;
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');

