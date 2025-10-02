<?php

use function App\Lib\{usuario_logado, set_flash, redirecionar};

require_once __DIR__ . '/../modelos/Config.php';
require_once __DIR__ . '/../config/db.php';

function painel(): void {
    $dir = realpath(__DIR__ . '/..' . '/..') . DIRECTORY_SEPARATOR . 'backups';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $arquivos = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $f) {
        $arquivos[] = [
            'nome' => basename($f),
            'tamanho' => filesize($f),
            'data' => date('Y-m-d H:i:s', filemtime($f)),
        ];
    }
    include __DIR__ . '/../visoes/backup/painel.php';
}

function baixar(): void {
    $nome = basename((string)($_GET['arquivo'] ?? ''));
    $dir = realpath(__DIR__ . '/..' . '/..') . DIRECTORY_SEPARATOR . 'backups';
    $caminho = $dir . DIRECTORY_SEPARATOR . $nome;
    if (!$nome || !is_file($caminho)) { http_response_code(404); echo 'Arquivo nÃ£o encontrado'; return; }
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $nome . '"');
    header('Content-Length: ' . filesize($caminho));
    readfile($caminho);
}

function excluir(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();

    $nome = basename((string)($_POST['arquivo'] ?? ''));
    if ($nome === '' || !preg_match('/\.sql$/i', $nome)) {
        set_flash('danger', 'Arquivo inválido.');
        redirecionar('backup.painel');
        return;
    }

    $dir = realpath(__DIR__ . '/..' . '/..') . DIRECTORY_SEPARATOR . 'backups';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $caminho = $dir . DIRECTORY_SEPARATOR . $nome;

    if (!is_file($caminho)) {
        set_flash('warning', 'Arquivo não encontrado.');
        redirecionar('backup.painel');
        return;
    }
    // Segurança extra: garante que o arquivo está dentro da pasta de backups
    $real = realpath($caminho);
    if ($real === false || strpos($real, $dir) !== 0) {
        set_flash('danger', 'Caminho inválido.');
        redirecionar('backup.painel');
        return;
    }

    if (@unlink($real)) {
        set_flash('success', 'Backup excluído: ' . htmlspecialchars($nome));
    } else {
        set_flash('danger', 'Não foi possível excluir o arquivo.');
    }
    redirecionar('backup.painel');
}

function gerar(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();
    $incluirDados = isset($_POST['incluir_dados']);

    $schema = $_ENV['DB_SCHEMA'] ?? 'adm';
    $pdo = App\DB\pdo();
    $pdo->exec("SET search_path TO \"{$schema}\", public");

    $dir = realpath(__DIR__ . '/..' . '/..') . DIRECTORY_SEPARATOR . 'backups';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $arquivo = $dir . DIRECTORY_SEPARATOR . 'backup_' . date('Ymd_His') . ($incluirDados ? '_full' : '_ddl') . '.sql';

    $fh = fopen($arquivo, 'w');
    if (!$fh) { set_flash('danger', 'NÃ£o foi possÃ­vel criar arquivo.'); redirecionar('backup.painel'); }

    $w = function(string $s) use ($fh){ fwrite($fh, $s); };

    // Metadados do servidor e do schema
    $dbVersionRow = $pdo->query("SELECT current_setting('server_version') AS v")->fetch(PDO::FETCH_ASSOC) ?: [];
    $dbVersion = (string)($dbVersionRow['v'] ?? '');
    $schemaOwnerStmt = $pdo->prepare("SELECT r.rolname AS owner FROM pg_namespace n JOIN pg_roles r ON r.oid=n.nspowner WHERE n.nspname=:s");
    $schemaOwnerStmt->execute([':s'=>$schema]);
    $schemaOwner = (string)($schemaOwnerStmt->fetch(PDO::FETCH_ASSOC)['owner'] ?? 'postgres');

    // Cabeçalho estilo pg_dump (aproximado)
    $w("--\n");
    $w("-- PostgreSQL database dump\n");
    $w("--\n\n");
    if ($dbVersion !== '') { $w("-- Dumped from database version {$dbVersion}\n"); }
    $w("-- Dumped by pg_dump version 17.4\n\n");
    $w("-- Started on " . date('Y-m-d H:i:s') . "\n\n");

    $w("SET statement_timeout = 0;\n");
    $w("SET lock_timeout = 0;\n");
    $w("SET idle_in_transaction_session_timeout = 0;\n");
    $w("SET transaction_timeout = 0;\n");
    $w("SET client_encoding = 'UTF8';\n");
    $w("SET standard_conforming_strings = on;\n");
    $w("SELECT pg_catalog.set_config('search_path', '', false);\n");
    $w("SET check_function_bodies = false;\n");
    $w("SET xmloption = content;\n");
    $w("SET client_min_messages = warning;\n");
    $w("SET row_security = off;\n\n");

    // Schema
    $w("--\n-- Schema: {$schema}\n--\n\n");
    $w("CREATE SCHEMA \"{$schema}\";\n\n");
    $w("ALTER SCHEMA \"{$schema}\" OWNER TO {$schemaOwner};\n\n");
    $w("SET default_tablespace = '';\n\n");
    $w("SET default_table_access_method = heap;\n\n");

    // Tabelas do schema
    $tabelas = $pdo->prepare("SELECT c.relname AS tabela FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace WHERE c.relkind='r' AND n.nspname=:s ORDER BY c.relname");
    $tabelas->execute([':s' => $schema]);
    $lista = array_map(fn($r)=>$r['tabela'], $tabelas->fetchAll(PDO::FETCH_ASSOC) ?: []);
    $fkDefs = [];
    $dataBlocks = [];
    $seqSetVals = [];
    $seenSeqForSetval = [];

    foreach ($lista as $t) {
        $ident = '"' . $schema . '"."' . $t . '"';
        // DDL bÃ¡sica
        $cols = $pdo->prepare("SELECT a.attnum, a.attname, format_type(a.atttypid,a.atttypmod) AS tipo, a.attnotnull, pg_get_expr(d.adbin,d.adrelid) AS def
                                FROM pg_attribute a
                                LEFT JOIN pg_attrdef d ON d.adrelid=a.attrelid AND d.adnum=a.attnum
                                WHERE a.attrelid = :rel::regclass AND a.attnum>0 AND NOT a.attisdropped
                                ORDER BY a.attnum");
        $cols->execute([':rel' => $ident]);
        $colDefs = [];
        foreach ($cols->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $tipo = strtolower((string)$c['tipo']);
            $def  = trim((string)($c['def'] ?? ''));
            $isNextval = ($def !== '' && preg_match("/^nextval\('([^']+)'::regclass\)$/", $def));
            if ($isNextval && in_array($tipo, ['integer','int4','bigint','int8','smallint','int2'], true)) {
                // Usa serial/bigserial/smallserial para criar a sequÃªncia automaticamente
                if ($tipo === 'bigint' || $tipo === 'int8') {
                    $line = '"' . $c['attname'] . '" bigserial';
                } elseif ($tipo === 'smallint' || $tipo === 'int2') {
                    $line = '"' . $c['attname'] . '" smallserial';
                } else {
                    $line = '"' . $c['attname'] . '" serial';
                }
            } else {
                $line = '"' . $c['attname'] . '" ' . $c['tipo'];
                if ($def !== '') { $line .= ' DEFAULT ' . $def; }
            }
            if (!empty($c['attnotnull'])) { $line .= ' NOT NULL'; }
            $colDefs[] = $line;
        }
        $w("-- \n-- Estrutura de tabela {$ident}\n-- \n");
        // (sem DROP TABLE para seguir o estilo do pg_dump)
        $w("CREATE TABLE {$ident} (\n  " . implode(",\n  ", $colDefs) . "\n);\n\n");
        // Define OWNER como no dump do pg_dump
        $ownStmt2 = $pdo->prepare("SELECT r.rolname AS owner FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace JOIN pg_roles r ON r.oid=c.relowner WHERE n.nspname=:s AND c.relname=:t");
        $ownStmt2->execute([':s'=>$schema, ':t'=>$t]);
        $tblOwner2 = (string)($ownStmt2->fetch(PDO::FETCH_ASSOC)['owner'] ?? 'postgres');
        $w("ALTER TABLE {$ident} OWNER TO {$tblOwner2};\n\n");

        // Constraints
        $cons = $pdo->prepare("SELECT conname, contype, pg_get_constraintdef(oid) AS def FROM pg_constraint WHERE conrelid = :rel::regclass ORDER BY contype DESC, conname");
        $cons->execute([':rel' => $ident]);
        foreach ($cons->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if (($r['contype'] ?? '') === 'f') {
                $fkDefs[] = 'ALTER TABLE ' . $ident . ' ADD CONSTRAINT "' . $r['conname'] . '" ' . $r['def'] . ';';
            } else {
                $w('ALTER TABLE ' . $ident . ' ADD CONSTRAINT "' . $r['conname'] . '" ' . $r['def'] . ';' . "\n");
            }
        }
        $w("\n");

        // Indexes nÃ£o PK/UK
        $idx = $pdo->prepare("SELECT indexname, indexdef FROM pg_indexes WHERE schemaname=:s AND tablename=:t ORDER BY indexname");
        $idx->execute([':s'=>$schema, ':t'=>$t]);
        foreach ($idx->fetchAll(PDO::FETCH_ASSOC) as $ir) {
            if (strpos($ir['indexdef'], ' pkey ')!==false) continue;
            $w($ir['indexdef'] . ";\n");
        }
        $w("\n");

        // Dados: coletar e escrever ao final (após toda a estrutura)
        if ($incluirDados) {
            $q = $pdo->query('SELECT * FROM ' . $ident);
            $header = "-- Dados de {$ident}\n";
            $block = '';
            $count = 0;
            while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
                $vals = [];
                foreach ($row as $v) {
                    if ($v === null) { $vals[] = 'NULL'; }
                    elseif (is_bool($v)) { $vals[] = $v ? 'true' : 'false'; }
                    elseif (is_numeric($v) && !preg_match('/^0[0-9]/', (string)$v)) { $vals[] = (string)$v; }
                    else { $vals[] = $pdo->quote((string)$v); }
                }
                // No padrão do pg_dump (--inserts), não lista colunas
                $block .= 'INSERT INTO ' . $ident . ' VALUES (' . implode(', ', $vals) . ');' . "\n";
                $count++;
                if ($count % 1000 === 0) { $dataBlocks[] = $header . $block . "\n"; $header=''; $block=''; }
            }
            if ($block !== '') { $dataBlocks[] = $header . $block . "\n"; }
        }
    }

    // Após criar todas as tabelas e índices, aplica FKs (que referenciam outras tabelas)
    if (!empty($fkDefs)) {
        $w("--\n-- Foreign keys (aplicadas após todas as tabelas)\n--\n");
        foreach ($fkDefs as $fk) { $w($fk . "\n"); }
        $w("\n");
    }

    // Após a estrutura completa (tabelas, índices e FKs), escreve todos os INSERTs
    if (!empty($dataBlocks)) {
        $w("--\n-- Dados\n--\n");
        foreach ($dataBlocks as $blk) { $w($blk); }
        $w("\n");
    }

    // Coleta sequences do schema para setval() pós-inserção
    if ($incluirDados) {
        $seqs = $pdo->prepare("SELECT schemaname, sequencename FROM pg_sequences WHERE schemaname=:s ORDER BY sequencename");
        $seqs->execute([':s'=>$schema]);
        foreach ($seqs->fetchAll(PDO::FETCH_ASSOC) ?: [] as $sr) {
            $schemaname = (string)$sr['schemaname'];
            $sequencename = (string)$sr['sequencename'];
            $q = 'SELECT last_value, is_called FROM "' . str_replace('"','""',$schemaname) . '"."' . str_replace('"','""',$sequencename) . '"';
            try {
                $row = $pdo->query($q)->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) { $row = []; }
            $last = isset($row['last_value']) ? (string)$row['last_value'] : '1';
            $isCalledRaw = $row['is_called'] ?? true;
            $useCalled = is_bool($isCalledRaw) ? $isCalledRaw : in_array((string)$isCalledRaw, ['t','true','1'], true);
            $seqSetVals[] = "SELECT pg_catalog.setval('{$schemaname}.{$sequencename}', {$last}, " . ($useCalled ? 'true' : 'false') . ");\n";
        }
    }

    // Emite setval() das sequences pós-dados
    if ($incluirDados && !empty($seqSetVals)) {
        $w("--\n-- Ajuste de sequences\n--\n");
        foreach ($seqSetVals as $sv) { $w($sv); }
        $w("\n");
    }

    fclose($fh);
    set_flash('success', 'Backup gerado: ' . basename($arquivo));
    redirecionar('backup.painel');
}

