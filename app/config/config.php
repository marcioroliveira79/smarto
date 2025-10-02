<?php
declare(strict_types=1);

namespace App\Config;

const APP_NAME = 'Smarto';

function base_path(): string {
    // 1) Se informado no .env, respeita
    $base = $_ENV['BASE_PATH'] ?? '';
    if ($base !== '') {
        if ($base[0] !== '/') { $base = '/' . $base; }
        return rtrim($base, '/');
    }
    // 2) Auto-detecta pelo SCRIPT_NAME (ex.: /smarto/index.php -> /smarto)
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $dir = str_replace('\\', '/', dirname($script));
    $dir = rtrim($dir, '/');
    return $dir === '/' ? '' : $dir;
}
