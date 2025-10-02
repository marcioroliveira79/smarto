<?php
declare(strict_types=1);

namespace App\Modelos\Menu;

use App\DB;
use PDO;

function itens_por_perfil(int $perfilId): array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT mi.* FROM perfil_menu_item pmi JOIN menu_item mi ON mi.id = pmi.menu_item_id WHERE pmi.perfil_id = :id ORDER BY mi.ordem');
    $st->execute([':id' => $perfilId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// ===== CRUD de Menu/Submenu/Item =====

function listar_menus(): array {
    $pdo = DB\pdo();
    $st = $pdo->query('SELECT * FROM menu ORDER BY ordem, id');
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function buscar_menu(int $id): ?array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM menu WHERE id = :id');
    $st->execute([':id' => $id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function criar_menu(string $nome, string $icone, int $ordem, bool $ativo): int {
    $pdo = DB\pdo();
    if ($ordem <= 0) {
        $ordem = (int)($pdo->query('SELECT COALESCE(MAX(ordem),0)+1 FROM menu')->fetchColumn() ?: 1);
    }
    $st = $pdo->prepare('INSERT INTO menu (nome, icone, ordem, ativo) VALUES (:n,:i,:o,:a) RETURNING id');
    $st->bindValue(':n', $nome);
    $st->bindValue(':i', $icone);
    $st->bindValue(':o', $ordem, PDO::PARAM_INT);
    $st->bindValue(':a', $ativo, PDO::PARAM_BOOL);
    $st->execute();
    return (int)$st->fetchColumn();
}

function atualizar_menu(int $id, string $nome, string $icone, int $ordem, bool $ativo): bool {
    $pdo = DB\pdo();
    if ($ordem > 0) {
        $st = $pdo->prepare('UPDATE menu SET nome=:n, icone=:i, ordem=:o, ativo=:a WHERE id=:id');
        $st->bindValue(':o', $ordem, PDO::PARAM_INT);
    } else {
        $st = $pdo->prepare('UPDATE menu SET nome=:n, icone=:i, ativo=:a WHERE id=:id');
    }
    $st->bindValue(':n', $nome);
    $st->bindValue(':i', $icone);
    $st->bindValue(':a', $ativo, PDO::PARAM_BOOL);
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    return $st->execute();
}

function excluir_menu(int $id): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare('DELETE FROM menu WHERE id=:id');
    return $st->execute([':id'=>$id]);
}

function listar_submenus(int $menu_id): array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM submenu WHERE menu_id = :id ORDER BY ordem, id');
    $st->execute([':id'=>$menu_id]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function buscar_submenu(int $id): ?array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM submenu WHERE id = :id');
    $st->execute([':id'=>$id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function criar_submenu(int $menu_id, string $nome, string $icone, int $ordem, bool $ativo): int {
    $pdo = DB\pdo();
    if ($ordem <= 0) {
        $q = $pdo->prepare('SELECT COALESCE(MAX(ordem),0)+1 FROM submenu WHERE menu_id = :m');
        $q->execute([':m' => $menu_id]);
        $ordem = (int)($q->fetchColumn() ?: 1);
    }
    $st = $pdo->prepare('INSERT INTO submenu (menu_id, nome, icone, ordem, ativo) VALUES (:m,:n,:i,:o,:a) RETURNING id');
    $st->bindValue(':m', $menu_id, PDO::PARAM_INT);
    $st->bindValue(':n', $nome);
    $st->bindValue(':i', $icone);
    $st->bindValue(':o', $ordem, PDO::PARAM_INT);
    $st->bindValue(':a', $ativo, PDO::PARAM_BOOL);
    $st->execute();
    return (int)$st->fetchColumn();
}

function atualizar_submenu(int $id, int $menu_id, string $nome, string $icone, int $ordem, bool $ativo): bool {
    $pdo = DB\pdo();
    if ($ordem > 0) {
        $st = $pdo->prepare('UPDATE submenu SET menu_id=:m, nome=:n, icone=:i, ordem=:o, ativo=:a WHERE id=:id');
        $st->bindValue(':o', $ordem, PDO::PARAM_INT);
    } else {
        $st = $pdo->prepare('UPDATE submenu SET menu_id=:m, nome=:n, icone=:i, ativo=:a WHERE id=:id');
    }
    $st->bindValue(':m', $menu_id, PDO::PARAM_INT);
    $st->bindValue(':n', $nome);
    $st->bindValue(':i', $icone);
    $st->bindValue(':a', $ativo, PDO::PARAM_BOOL);
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    return $st->execute();
}

function excluir_submenu(int $id): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare('DELETE FROM submenu WHERE id=:id');
    return $st->execute([':id'=>$id]);
}

function listar_itens_por_menu(int $menu_id): array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM menu_item WHERE menu_id = :m AND submenu_id IS NULL ORDER BY ordem, id');
    $st->execute([':m'=>$menu_id]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function listar_itens_por_submenu(int $submenu_id): array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM menu_item WHERE submenu_id = :s ORDER BY ordem, id');
    $st->execute([':s'=>$submenu_id]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function buscar_item(int $id): ?array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT * FROM menu_item WHERE id=:id');
    $st->execute([':id'=>$id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function criar_item(?int $menu_id, ?int $submenu_id, string $nome, string $icone, string $rota_acao, int $ordem, bool $ativo): int {
    $pdo = DB\pdo();
    if ($ordem <= 0) {
        if ($submenu_id) {
            $q = $pdo->prepare('SELECT COALESCE(MAX(ordem),0)+1 FROM menu_item WHERE submenu_id = :s');
            $q->execute([':s' => $submenu_id]);
            $ordem = (int)($q->fetchColumn() ?: 1);
        } else {
            $q = $pdo->prepare('SELECT COALESCE(MAX(ordem),0)+1 FROM menu_item WHERE menu_id = :m AND submenu_id IS NULL');
            $q->execute([':m' => $menu_id]);
            $ordem = (int)($q->fetchColumn() ?: 1);
        }
    }
    $st = $pdo->prepare('INSERT INTO menu_item (menu_id, submenu_id, nome, icone, rota_acao, ordem, ativo)
                         VALUES (:m,:s,:n,:i,:r,:o,:a) RETURNING id');
    if ($menu_id === null) { $st->bindValue(':m', null, PDO::PARAM_NULL); } else { $st->bindValue(':m', $menu_id, PDO::PARAM_INT); }
    if ($submenu_id === null) { $st->bindValue(':s', null, PDO::PARAM_NULL); } else { $st->bindValue(':s', $submenu_id, PDO::PARAM_INT); }
    $st->bindValue(':n', $nome);
    $st->bindValue(':i', $icone);
    $st->bindValue(':r', $rota_acao);
    $st->bindValue(':o', $ordem, PDO::PARAM_INT);
    $st->bindValue(':a', $ativo, PDO::PARAM_BOOL);
    $st->execute();
    return (int)$st->fetchColumn();
}

function atualizar_item(int $id, ?int $menu_id, ?int $submenu_id, string $nome, string $icone, string $rota_acao, int $ordem, bool $ativo): bool {
    $pdo = DB\pdo();
    if ($ordem > 0) {
        $st = $pdo->prepare('UPDATE menu_item SET menu_id=:m, submenu_id=:s, nome=:n, icone=:i, rota_acao=:r, ordem=:o, ativo=:a WHERE id=:id');
        $st->bindValue(':o', $ordem, PDO::PARAM_INT);
    } else {
        $st = $pdo->prepare('UPDATE menu_item SET menu_id=:m, submenu_id=:s, nome=:n, icone=:i, rota_acao=:r, ativo=:a WHERE id=:id');
    }
    if ($menu_id === null) { $st->bindValue(':m', null, PDO::PARAM_NULL); } else { $st->bindValue(':m', $menu_id, PDO::PARAM_INT); }
    if ($submenu_id === null) { $st->bindValue(':s', null, PDO::PARAM_NULL); } else { $st->bindValue(':s', $submenu_id, PDO::PARAM_INT); }
    $st->bindValue(':n', $nome);
    $st->bindValue(':i', $icone);
    $st->bindValue(':r', $rota_acao);
    $st->bindValue(':a', $ativo, PDO::PARAM_BOOL);
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    return $st->execute();
}

function excluir_item(int $id): bool {
    $pdo = DB\pdo();
    $st = $pdo->prepare('DELETE FROM menu_item WHERE id=:id');
    return $st->execute([':id'=>$id]);
}

// ===== Vínculo item x perfil =====

function listar_perfis(): array {
    $pdo = DB\pdo();
    $st = $pdo->query('SELECT id, nome, ativo FROM perfil ORDER BY nome');
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// ===== Reordenação (setas) =====

function mover_menu(int $id, string $dir): bool {
    $pdo = DB\pdo();
    // Normaliza ordens para evitar duplicadas
    renumerar_menus();
    $cur = $pdo->prepare('SELECT id, ordem FROM menu WHERE id = :id');
    $cur->execute([':id' => $id]);
    $c = $cur->fetch(PDO::FETCH_ASSOC);
    if (!$c) return false;
    $params = [':o' => (int)$c['ordem'], ':id' => (int)$c['id']];
    if ($dir === 'up') {
        $sql = 'SELECT id, ordem FROM menu WHERE (ordem < :o OR (ordem = :o AND id < :id)) ORDER BY ordem DESC, id DESC LIMIT 1';
    } else {
        $sql = 'SELECT id, ordem FROM menu WHERE (ordem > :o OR (ordem = :o AND id > :id)) ORDER BY ordem ASC, id ASC LIMIT 1';
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $n = $st->fetch(PDO::FETCH_ASSOC);
    if (!$n) return true; // nada a mover
    $pdo->beginTransaction();
    try {
        $u1 = $pdo->prepare('UPDATE menu SET ordem = :o WHERE id = :id');
        $u1->execute([':o' => (int)$n['ordem'], ':id' => (int)$c['id']]);
        $u2 = $pdo->prepare('UPDATE menu SET ordem = :o WHERE id = :id');
        $u2->execute([':o' => (int)$c['ordem'], ':id' => (int)$n['id']]);
        $pdo->commit();
        return true;
    } catch (\Throwable $e) { $pdo->rollBack(); return false; }
}

function mover_submenu(int $id, string $dir): bool {
    $pdo = DB\pdo();
    $cur = $pdo->prepare('SELECT id, menu_id, ordem FROM submenu WHERE id = :id');
    $cur->execute([':id' => $id]);
    $c = $cur->fetch(PDO::FETCH_ASSOC);
    if (!$c) return false;
    renumerar_submenus((int)$c['menu_id']);
    $params = [':o' => (int)$c['ordem'], ':id' => (int)$c['id'], ':m' => (int)$c['menu_id']];
    if ($dir === 'up') {
        $sql = 'SELECT id, ordem FROM submenu WHERE menu_id = :m AND (ordem < :o OR (ordem = :o AND id < :id)) ORDER BY ordem DESC, id DESC LIMIT 1';
    } else {
        $sql = 'SELECT id, ordem FROM submenu WHERE menu_id = :m AND (ordem > :o OR (ordem = :o AND id > :id)) ORDER BY ordem ASC, id ASC LIMIT 1';
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $n = $st->fetch(PDO::FETCH_ASSOC);
    if (!$n) return true;
    $pdo->beginTransaction();
    try {
        $u1 = $pdo->prepare('UPDATE submenu SET ordem = :o WHERE id = :id');
        $u1->execute([':o' => (int)$n['ordem'], ':id' => (int)$c['id']]);
        $u2 = $pdo->prepare('UPDATE submenu SET ordem = :o WHERE id = :id');
        $u2->execute([':o' => (int)$c['ordem'], ':id' => (int)$n['id']]);
        $pdo->commit();
        return true;
    } catch (\Throwable $e) { $pdo->rollBack(); return false; }
}

function mover_item(int $id, string $dir): bool {
    $pdo = DB\pdo();
    $cur = $pdo->prepare('SELECT id, menu_id, submenu_id, ordem FROM menu_item WHERE id = :id');
    $cur->execute([':id' => $id]);
    $c = $cur->fetch(PDO::FETCH_ASSOC);
    if (!$c) return false;
    // Normaliza a coleção adequada
    if (!empty($c['submenu_id'])) { renumerar_itens((int)$c['menu_id'], (int)$c['submenu_id']); }
    else { renumerar_itens((int)$c['menu_id'], null); }
    $cond = '';
    $params = [':o' => (int)$c['ordem'], ':id' => (int)$c['id']];
    if (!empty($c['submenu_id'])) {
        $cond = 'submenu_id = :sid';
        $params[':sid'] = (int)$c['submenu_id'];
    } else {
        $cond = 'menu_id = :mid AND submenu_id IS NULL';
        $params[':mid'] = (int)$c['menu_id'];
    }
    if ($dir === 'up') {
        $sql = "SELECT id, ordem FROM menu_item WHERE $cond AND (ordem < :o OR (ordem = :o AND id < :id)) ORDER BY ordem DESC, id DESC LIMIT 1";
    } else {
        $sql = "SELECT id, ordem FROM menu_item WHERE $cond AND (ordem > :o OR (ordem = :o AND id > :id)) ORDER BY ordem ASC, id ASC LIMIT 1";
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $n = $st->fetch(PDO::FETCH_ASSOC);
    if (!$n) return true;
    $pdo->beginTransaction();
    try {
        $u1 = $pdo->prepare('UPDATE menu_item SET ordem = :o WHERE id = :id');
        $u1->execute([':o' => (int)$n['ordem'], ':id' => (int)$c['id']]);
        $u2 = $pdo->prepare('UPDATE menu_item SET ordem = :o WHERE id = :id');
        $u2->execute([':o' => (int)$c['ordem'], ':id' => (int)$n['id']]);
        $pdo->commit();
        return true;
    } catch (\Throwable $e) { $pdo->rollBack(); return false; }
}

// Move item para outro container (raiz de um menu ou um submenu), inclusive entre menus diferentes
function mover_item_para2(int $item_id, int $menu_id, ?int $submenu_id): bool {
    $pdo = DB\pdo();
    $it = $pdo->prepare('SELECT id, menu_id, submenu_id FROM menu_item WHERE id = :id');
    $it->execute([':id' => $item_id]);
    $c = $it->fetch(PDO::FETCH_ASSOC);
    if (!$c) return false;

    $origSubmenuId = !empty($c['submenu_id']) ? (int)$c['submenu_id'] : null;
    $origMenuId = !empty($c['menu_id']) ? (int)$c['menu_id'] : null;
    if (!$origMenuId && $origSubmenuId) {
        $q = $pdo->prepare('SELECT menu_id FROM submenu WHERE id = :sid');
        $q->execute([':sid' => $origSubmenuId]);
        $origMenuId = (int)($q->fetchColumn() ?: 0);
    }

    // Descobre menu de destino quando o alvo é um submenu
    $targetMenuId = (int)$menu_id;
    if ($submenu_id) {
        $q = $pdo->prepare('SELECT menu_id FROM submenu WHERE id = :sid');
        $q->execute([':sid' => $submenu_id]);
        $targetMenuId = (int)($q->fetchColumn() ?: 0);
        if ($targetMenuId <= 0) return false;
    }

    // Nova ordem no destino
    if ($submenu_id) {
        $st = $pdo->prepare('SELECT COALESCE(MAX(ordem),0)+1 FROM menu_item WHERE submenu_id = :sid');
        $st->execute([':sid' => $submenu_id]);
    } else {
        $st = $pdo->prepare('SELECT COALESCE(MAX(ordem),0)+1 FROM menu_item WHERE menu_id = :mid AND submenu_id IS NULL');
        $st->execute([':mid' => $targetMenuId]);
    }
    $nova = (int)$st->fetchColumn();

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare('UPDATE menu_item SET menu_id = :mid, submenu_id = :sid, ordem = :o WHERE id = :id');
        if ($submenu_id) {
            $upd->bindValue(':mid', null, PDO::PARAM_NULL);
            $upd->bindValue(':sid', $submenu_id, PDO::PARAM_INT);
        } else {
            $upd->bindValue(':mid', $targetMenuId, PDO::PARAM_INT);
            $upd->bindValue(':sid', null, PDO::PARAM_NULL);
        }
        $upd->bindValue(':o', $nova, PDO::PARAM_INT);
        $upd->bindValue(':id', $item_id, PDO::PARAM_INT);
        $upd->execute();

        if ($origMenuId > 0) {
            if ($origSubmenuId) { renumerar_itens($origMenuId, $origSubmenuId); }
            else { renumerar_itens($origMenuId, null); }
        }

        $pdo->commit();
        return true;
    } catch (\Throwable $e) { $pdo->rollBack(); return false; }
}

// Move um submenu para outro menu, preservando itens, colocando no final do nível
function mover_submenu_para(int $submenu_id, int $novo_menu_id): bool {
    $pdo = DB\pdo();
    $cur = $pdo->prepare('SELECT id, menu_id FROM submenu WHERE id = :id');
    $cur->execute([':id' => $submenu_id]);
    $c = $cur->fetch(PDO::FETCH_ASSOC);
    if (!$c) return false;
    $orig_menu_id = (int)$c['menu_id'];
    if ($orig_menu_id === $novo_menu_id) return true;

    $maxSub = $pdo->prepare('SELECT COALESCE(MAX(ordem),0) FROM submenu WHERE menu_id = :m');
    $maxSub->execute([':m' => $novo_menu_id]);
    $a = (int)($maxSub->fetchColumn() ?: 0);
    $maxIt = $pdo->prepare('SELECT COALESCE(MAX(ordem),0) FROM menu_item WHERE menu_id = :m AND submenu_id IS NULL');
    $maxIt->execute([':m' => $novo_menu_id]);
    $b = (int)($maxIt->fetchColumn() ?: 0);
    $nova = max($a, $b) + 1;

    $pdo->beginTransaction();
    try {
        $u = $pdo->prepare('UPDATE submenu SET menu_id = :m, ordem = :o WHERE id = :id');
        $u->execute([':m' => $novo_menu_id, ':o' => $nova, ':id' => $submenu_id]);
        renumerar_submenus($orig_menu_id);
        renumerar_nivel_menu($novo_menu_id);
        $pdo->commit();
        return true;
    } catch (\Throwable $e) { $pdo->rollBack(); return false; }
}
function mover_item_para(int $item_id, int $menu_id, ?int $submenu_id): bool {
    $pdo = DB\pdo();
    $it = $pdo->prepare('SELECT id, menu_id, submenu_id FROM menu_item WHERE id = :id');
    $it->execute([':id' => $item_id]);
    $c = $it->fetch(PDO::FETCH_ASSOC);
    if (!$c) return false;
    // Apenas dentro do mesmo menu. Quando o item está em um submenu, menu_id pode ser NULL;
    // derive o menu atual via submenu se necessário.
    $currentMenuId = (int)($c['menu_id'] ?? 0);
    if (!$currentMenuId && !empty($c['submenu_id'])) {
        $q = $pdo->prepare('SELECT menu_id FROM submenu WHERE id = :sid');
        $q->execute([':sid' => (int)$c['submenu_id']]);
        $currentMenuId = (int)($q->fetchColumn() ?: 0);
    }
    if ($currentMenuId !== (int)$menu_id) return false;
    // Nova ordem no destino
    if ($submenu_id) {
        $st = $pdo->prepare('SELECT COALESCE(MAX(ordem),0)+1 FROM menu_item WHERE submenu_id = :sid');
        $st->execute([':sid' => $submenu_id]);
    } else {
        $st = $pdo->prepare('SELECT COALESCE(MAX(ordem),0)+1 FROM menu_item WHERE menu_id = :mid AND submenu_id IS NULL');
        $st->execute([':mid' => $menu_id]);
    }
    $nova = (int)$st->fetchColumn();
    // Ao mover: se for para raiz, submenu_id = NULL e menu_id deve ser o menu alvo
    // Se for para um submenu, menu_id = NULL e submenu_id aponta para o destino
    $upd = $pdo->prepare('UPDATE menu_item SET menu_id = :mid, submenu_id = :sid, ordem = :o WHERE id = :id');
    if ($submenu_id) {
        $upd->bindValue(':mid', null, PDO::PARAM_NULL);
        $upd->bindValue(':sid', $submenu_id, PDO::PARAM_INT);
    } else {
        $upd->bindValue(':mid', $menu_id, PDO::PARAM_INT);
        $upd->bindValue(':sid', null, PDO::PARAM_NULL);
    }
    $upd->bindValue(':o', $nova, PDO::PARAM_INT);
    $upd->bindValue(':id', $item_id, PDO::PARAM_INT);
    return $upd->execute();
}

// ===== Normalização de ordens =====
function renumerar_menus(): void {
    $pdo = DB\pdo();
    $ids = $pdo->query('SELECT id FROM menu ORDER BY ordem, id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $ord = 1;
    $st = $pdo->prepare('UPDATE menu SET ordem = :o WHERE id = :id');
    foreach ($ids as $id) { $st->execute([':o' => $ord++, ':id' => (int)$id]); }
}

function renumerar_submenus(int $menu_id): void {
    $pdo = DB\pdo();
    $stSel = $pdo->prepare('SELECT id FROM submenu WHERE menu_id = :m ORDER BY ordem, id');
    $stSel->execute([':m' => $menu_id]);
    $ids = $stSel->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $ord = 1; $st = $pdo->prepare('UPDATE submenu SET ordem = :o WHERE id = :id');
    foreach ($ids as $id) { $st->execute([':o' => $ord++, ':id' => (int)$id]); }
}

function renumerar_itens(int $menu_id, ?int $submenu_id): void {
    $pdo = DB\pdo();
    if ($submenu_id) {
        $stSel = $pdo->prepare('SELECT id FROM menu_item WHERE submenu_id = :s ORDER BY ordem, id');
        $stSel->execute([':s' => $submenu_id]);
    } else {
        $stSel = $pdo->prepare('SELECT id FROM menu_item WHERE menu_id = :m AND submenu_id IS NULL ORDER BY ordem, id');
        $stSel->execute([':m' => $menu_id]);
    }
    $ids = $stSel->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $ord = 1; $st = $pdo->prepare('UPDATE menu_item SET ordem = :o WHERE id = :id');
    foreach ($ids as $id) { $st->execute([':o' => $ord++, ':id' => (int)$id]); }
}

function renumerar_nivel_menu(int $menu_id): void {
    $pdo = DB\pdo();
    // Coleta submenus e itens de raiz
    $subs = $pdo->prepare('SELECT id, ordem, 1 AS tipo_ord FROM submenu WHERE menu_id = :m');
    $subs->execute([':m' => $menu_id]);
    $subRows = $subs->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $its = $pdo->prepare('SELECT id, ordem, 2 AS tipo_ord FROM menu_item WHERE menu_id = :m AND submenu_id IS NULL');
    $its->execute([':m' => $menu_id]);
    $itRows = $its->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $all = array_merge(array_map(function($r){ $r['tipo']='submenu'; return $r; }, $subRows),
                      array_map(function($r){ $r['tipo']='item'; return $r; }, $itRows));
    usort($all, function($a,$b){ if ($a['ordem']==$b['ordem']) return $a['tipo_ord']<=>$b['tipo_ord'] ?: $a['id']<=>$b['id']; return $a['ordem']<=>$b['ordem']; });
    $ord = 1;
    foreach ($all as $r) {
        if ($r['tipo']==='submenu') {
            $u = $pdo->prepare('UPDATE submenu SET ordem=:o WHERE id=:id');
            $u->execute([':o'=>$ord, ':id'=>(int)$r['id']]);
        } else {
            $u = $pdo->prepare('UPDATE menu_item SET ordem=:o WHERE id=:id');
            $u->execute([':o'=>$ord, ':id'=>(int)$r['id']]);
        }
        $ord++;
    }
}

function mover_nivel_menu(int $menu_id, string $tipo, int $id, string $dir): bool {
    $pdo = DB\pdo();
    renumerar_nivel_menu($menu_id);
    // monta lista combinada
    $q = $pdo->prepare("(SELECT id, ordem, 1 AS tipo_ord, 'submenu' AS tipo FROM submenu WHERE menu_id = :m) UNION ALL (SELECT id, ordem, 2 AS tipo_ord, 'item' AS tipo FROM menu_item WHERE menu_id = :m AND submenu_id IS NULL) ORDER BY ordem, tipo_ord, id");
    $q->execute([':m'=>$menu_id]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $idx = -1;
    for ($i=0;$i<count($rows);$i++) { if ($rows[$i]['tipo']===$tipo && (int)$rows[$i]['id']===$id) { $idx=$i; break; } }
    if ($idx<0) return false;
    $j = $dir==='up' ? $idx-1 : $idx+1;
    if ($j<0 || $j>=count($rows)) return true; // nada a mover
    $a = $rows[$idx]; $b = $rows[$j];
    $pdo->beginTransaction();
    try {
        // swap ordens
        if ($a['tipo']==='submenu') {
            $u = $pdo->prepare('UPDATE submenu SET ordem=:o WHERE id=:id');
            $u->execute([':o'=>(int)$b['ordem'], ':id'=>(int)$a['id']]);
        } else {
            $u = $pdo->prepare('UPDATE menu_item SET ordem=:o WHERE id=:id');
            $u->execute([':o'=>(int)$b['ordem'], ':id'=>(int)$a['id']]);
        }
        if ($b['tipo']==='submenu') {
            $u = $pdo->prepare('UPDATE submenu SET ordem=:o WHERE id=:id');
            $u->execute([':o'=>(int)$a['ordem'], ':id'=>(int)$b['id']]);
        } else {
            $u = $pdo->prepare('UPDATE menu_item SET ordem=:o WHERE id=:id');
            $u->execute([':o'=>(int)$a['ordem'], ':id'=>(int)$b['id']]);
        }
        $pdo->commit();
        return true;
    } catch (\Throwable $e) { $pdo->rollBack(); return false; }
}

function perfis_do_item(int $menu_item_id): array {
    $pdo = DB\pdo();
    $st = $pdo->prepare('SELECT perfil_id FROM perfil_menu_item WHERE menu_item_id = :id');
    $st->execute([':id' => $menu_item_id]);
    return array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC) ?: [], 'perfil_id'));
}

function salvar_perfis_do_item(int $menu_item_id, array $perfil_ids): bool {
    $pdo = DB\pdo();
    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare('DELETE FROM perfil_menu_item WHERE menu_item_id = :id');
        $del->execute([':id' => $menu_item_id]);
        if (!empty($perfil_ids)) {
            $ins = $pdo->prepare('INSERT INTO perfil_menu_item (perfil_id, menu_item_id) VALUES (:pid, :iid)');
            foreach ($perfil_ids as $pid) {
                $ins->execute([':pid' => (int)$pid, ':iid' => $menu_item_id]);
            }
        }
        $pdo->commit();
        return true;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        return false;
    }
}

