<?php
declare(strict_types=1);

namespace App\Lib\Migrador;

use function App\DB\pdo;
use Throwable;

/**
 * Executa todos os arquivos .sql em um diretório, em ordem, cada um em uma transação.
 * Retorna uma lista de resultados por arquivo.
 */
function executarArquivosDir(string $dir): array {
    $caminho = realpath($dir);
    $resultados = [];
    if (!$caminho || !is_dir($caminho)) {
        return [['arquivo' => $dir, 'ok' => false, 'erro' => 'Diretório não encontrado']];
    }
    $arquivos = array_values(array_filter(scandir($caminho) ?: [], function($f){ return preg_match('/\.sql$/i', $f); }));
    sort($arquivos, SORT_NATURAL);
    $db = pdo();
    foreach ($arquivos as $arq) {
        $path = $caminho . DIRECTORY_SEPARATOR . $arq;
        $sql = file_get_contents($path);
        if ($sql === false) {
            $resultados[] = ['arquivo' => $arq, 'ok' => false, 'erro' => 'Falha ao ler'];
            continue;
        }
        $db->beginTransaction();
        try {
            $db->exec($sql);
            $db->commit();
            $resultados[] = ['arquivo' => $arq, 'ok' => true];
        } catch (Throwable $e) {
            $db->rollBack();
            $resultados[] = ['arquivo' => $arq, 'ok' => false, 'erro' => $e->getMessage()];
            break; // interrompe na primeira falha
        }
    }
    return $resultados;
}

function aplicarTudo(string $baseDir): array {
    $out = [];
    $out['migracoes'] = executarArquivosDir($baseDir . '/db/migracoes');
    // Se houve falha nas migrações, não executa seeds
    $falhou = false;
    foreach ($out['migracoes'] as $r) { if (!$r['ok']) { $falhou = true; break; } }
    $out['semente'] = $falhou ? [] : executarArquivosDir($baseDir . '/db/semente');
    return $out;
}

