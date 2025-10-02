<?php
declare(strict_types=1);

// Executa migrações e seeds usando a conexão do app

require_once __DIR__ . '/../app/config/env.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/biblioteca/migrador.php';

use function App\Lib\Migrador\aplicarTudo;

$base = realpath(__DIR__ . '/..');
$res = aplicarTudo($base);

echo "== Migrações ==\n";
foreach ($res['migracoes'] as $r) {
    echo ($r['ok'] ? '[OK] ' : '[ERRO] ') . $r['arquivo'] . (!empty($r['erro']) ? (' - ' . $r['erro']) : '') . "\n";
}

echo "== Seeds ==\n";
foreach ($res['semente'] as $r) {
    echo ($r['ok'] ? '[OK] ' : '[ERRO] ') . $r['arquivo'] . (!empty($r['erro']) ? (' - ' . $r['erro']) : '') . "\n";
}

// Se houve algum erro, retorne código 1
$erro = false;
foreach (['migracoes','semente'] as $bloco) {
  foreach ($res[$bloco] as $r) { if (!$r['ok']) { $erro = true; break 2; } }
}
echo $erro ? "Concluído com erros.\n" : "Concluído.\n";
if ($erro) { exit(1); }
