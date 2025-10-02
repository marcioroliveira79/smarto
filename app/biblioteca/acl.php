<?php
declare(strict_types=1);

namespace App\Lib;

use App\DB;
use PDO;

function acl_permite_acao(string $acao): bool {
    $u = usuario_logado();
    if (!$u) return false;
    $pdo = DB\pdo();
    // 1) Tenta correspondência exata
    $q = $pdo->prepare('SELECT mi.rota_acao
        FROM menu_item mi
        JOIN perfil_menu_item pmi ON pmi.menu_item_id = mi.id
        WHERE pmi.perfil_id = :pid AND mi.ativo = true');
    $q->execute([':pid' => (int)$u['perfil_id']]);
    $rotas = array_column($q->fetchAll(PDO::FETCH_ASSOC) ?: [], 'rota_acao');
    if (in_array($acao, $rotas, true)) return true;
    // 2) Permite ações do mesmo módulo/prefixo (ex.: usuario.* se usuario.listar permitido)
    $prefix = explode('.', $acao, 2)[0] ?? '';
    foreach ($rotas as $r) {
        if (($rPrefix = (explode('.', $r, 2)[0] ?? '')) === $prefix) {
            return true;
        }
    }
    return false;
}

function carregar_menu_para_usuario(): array {
    $u = usuario_logado();
    if (!$u) return [];
    $pdo = DB\pdo();
  
    $sql = 'SELECT m.id AS menu_id, m.nome AS menu_nome, m.icone AS menu_icone, m.ordem AS menu_ordem,
                    sm.id AS submenu_id, sm.nome AS submenu_nome, sm.icone AS submenu_icone, sm.ordem AS submenu_ordem,
                    mi.id AS item_id, mi.nome AS item_nome, mi.icone AS item_icone, mi.rota_acao, mi.ordem AS item_ordem
            FROM perfil_menu_item pmi
            JOIN menu_item mi ON mi.id = pmi.menu_item_id AND mi.ativo = true
            LEFT JOIN submenu sm ON sm.id = mi.submenu_id AND sm.ativo = true
            LEFT JOIN menu m ON m.id = COALESCE(mi.menu_id, sm.menu_id) AND m.ativo = true
            WHERE pmi.perfil_id = :pid
            ORDER BY m.ordem, sm.ordem NULLS FIRST, mi.ordem';
    $st = $pdo->prepare($sql);
    $st->execute([':pid' => (int)$u['perfil_id']]);

    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $menus = [];
    $seenSub = [];
    foreach ($rows as $r) {
        $mid = (int)$r['menu_id'];
        // Ignora linhas sem menu válido (resultado de joins nulos)
        if ($mid <= 0) { continue; }
        if (!isset($menus[$mid])) {
            $menus[$mid] = [
                'id' => $mid,
                'nome' => $r['menu_nome'],
                'icone' => $r['menu_icone'],
                'submenus' => [],
                'itens' => [],
                'nivel' => [],
            ];
        }
        if (!empty($r['submenu_id'])) {
            $sid = (int)$r['submenu_id'];
            if (!isset($menus[$mid]['submenus'][$sid])) {
                $menus[$mid]['submenus'][$sid] = [
                    'id' => $sid,
                    'nome' => $r['submenu_nome'],
                    'icone' => $r['submenu_icone'],
                    'ordem' => (int)$r['submenu_ordem'],
                    'itens' => [],
                ];
            }
            $menus[$mid]['submenus'][$sid]['itens'][] = [
                'id' => (int)$r['item_id'],
                'nome' => $r['item_nome'],
                'icone' => $r['item_icone'],
                'rota_acao' => $r['rota_acao'],
                'ordem' => (int)$r['item_ordem'],
            ];
            if (empty($seenSub[$mid][$sid])) {
                $menus[$mid]['nivel'][] = ['tipo' => 'submenu', 'id' => $sid, 'ordem' => (int)$r['submenu_ordem']];
                $seenSub[$mid][$sid] = true;
            }
        } else {
            $menus[$mid]['itens'][] = [
                'id' => (int)$r['item_id'],
                'nome' => $r['item_nome'],
                'icone' => $r['item_icone'],
                'rota_acao' => $r['rota_acao'],
                'ordem' => (int)$r['item_ordem'],
            ];
            $menus[$mid]['nivel'][] = ['tipo' => 'item', 'id' => (int)$r['item_id'], 'ordem' => (int)$r['item_ordem']];
        }
    }
    // Ordena nível combinado
    foreach ($menus as &$mm) {
        if (!empty($mm['nivel'])) {
            usort($mm['nivel'], function($a, $b) {
                if ($a['ordem'] === $b['ordem']) {
                    $ta = ($a['tipo'] === 'submenu') ? 1 : 2;
                    $tb = ($b['tipo'] === 'submenu') ? 1 : 2;
                    if ($ta !== $tb) return $ta <=> $tb; // submenu primeiro
                    return $a['id'] <=> $b['id'];
                }
                return $a['ordem'] <=> $b['ordem'];
            });
        }
    }

    // Garantir que o Administrador veja o atalho "Menus e Itens"
    if (false) {
        $temGerenciador = false;
        foreach ($menus as $mm) {
            foreach ($mm['itens'] as $it) { if (($it['rota_acao'] ?? '') === 'menu.listar') { $temGerenciador = true; break 2; } }
            foreach ($mm['submenus'] as $sm) {
                foreach ($sm['itens'] as $it) { if (($it['rota_acao'] ?? '') === 'menu.listar') { $temGerenciador = true; break 3; } }
            }
        }
        if (!$temGerenciador) {
            // Tenta localizar menu de Administração
            $adminKey = null;
            foreach ($menus as $k => $mm) {
                $nome = strtolower((string)($mm['nome'] ?? ''));
                if (str_starts_with($nome, 'admin')) { $adminKey = $k; break; }
            }
            if ($adminKey === null) {
                // Cria um menu virtual de Administração
                $adminKey = 0;
                $menus[$adminKey] = [
                    'id' => 0,
                    'nome' => 'Administração',
                    'icone' => 'fa-gear',
                    'submenus' => [],
                    'itens' => [],
                ];
            }
            $menus[$adminKey]['itens'][] = [
                'id' => 0,
                'nome' => 'Menus e Itens',
                'icone' => 'fa-sitemap',
                'rota_acao' => 'menu.listar',
            ];
        }
    }

    return array_values($menus);
}

