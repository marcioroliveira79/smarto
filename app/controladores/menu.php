<?php
declare(strict_types=1);

use App\Modelos\Menu as MenuModel;
use function App\Lib\{set_flash, redirecionar};
use function App\Lib\CSRF\{input as csrf_input, validar as csrf_validar};

require_once __DIR__ . '/../modelos/Menu.php';

function listar(): void {
    // Carrega toda a árvore: menus -> submenus -> itens
    $menusBase = MenuModel\listar_menus();
    $arvore = [];
    $todosItensIds = [];
    foreach ($menusBase as $m) {
        $m['itens'] = MenuModel\listar_itens_por_menu((int)$m['id']);
        foreach ($m['itens'] as $it) { $todosItensIds[] = (int)$it['id']; }
        $subsOrig = MenuModel\listar_submenus((int)$m['id']);
        $subs = [];
        foreach ($subsOrig as $sm) {
            $sm['itens'] = MenuModel\listar_itens_por_submenu((int)$sm['id']);
            foreach ($sm['itens'] as $it) { $todosItensIds[] = (int)$it['id']; }
            $subs[] = $sm;
        }
        $m['submenus'] = $subs;
        $arvore[] = $m;
    }

    // Perfis e permissões por item (mapa item_id => [perfil_ids])
    $perfis = MenuModel\listar_perfis();
    $permissoesMap = [];
    foreach ($todosItensIds as $iid) {
        $permissoesMap[$iid] = MenuModel\perfis_do_item($iid);
    }

    $flash = App\Lib\get_flash();
    if ($flash && (($flash['tipo'] ?? '') === 'success')) { $flash = null; }
    include __DIR__ . '/../visoes/menu/listar.php';
}

function nav(): void {
    // Retorna os menus disponíveis para o usuário atual em JSON
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    try {
        $menus = \App\Lib\carregar_menu_para_usuario();
        echo json_encode(['ok' => true, 'menus' => $menus], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false]);
    }
}

function salvar(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $tipo = (string)($_POST['tipo'] ?? '');

    switch ($tipo) {
        case 'menu':
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim((string)($_POST['nome'] ?? ''));
            $icone = trim((string)($_POST['icone'] ?? ''));
            $ordem = (int)($_POST['ordem'] ?? 0);
            $ativo = isset($_POST['ativo']);
            if ($nome === '') { set_flash('danger', 'Informe o nome do menu.'); redirecionar('menu.listar'); }
            if ($id) {
                MenuModel\atualizar_menu($id, $nome, $icone, $ordem, $ativo);
                redirecionar('menu.listar', ['menu_id' => $id]);
            } else {
                $novo = MenuModel\criar_menu($nome, $icone, $ordem, $ativo);
                redirecionar('menu.listar', ['menu_id' => $novo]);
            }
            break;
        case 'submenu':
            $id = (int)($_POST['id'] ?? 0);
            $menu_id = (int)($_POST['menu_id'] ?? 0);
            $nome = trim((string)($_POST['nome'] ?? ''));
            $icone = trim((string)($_POST['icone'] ?? ''));
            $ordem = (int)($_POST['ordem'] ?? 0);
            $ativo = isset($_POST['ativo']);
            if ($menu_id <= 0 || $nome === '') { set_flash('danger', 'Informe o menu e nome do submenu.'); redirecionar('menu.listar'); }
            if ($id) {
                MenuModel\atualizar_submenu($id, $menu_id, $nome, $icone, $ordem, $ativo);
                redirecionar('menu.listar', ['menu_id' => $menu_id, 'submenu_id' => $id]);
            } else {
                $novo = MenuModel\criar_submenu($menu_id, $nome, $icone, $ordem, $ativo);
                redirecionar('menu.listar', ['menu_id' => $menu_id, 'submenu_id' => $novo]);
            }
            break;
        case 'item':
            $id = (int)($_POST['id'] ?? 0);
            $menu_id = (int)($_POST['menu_id'] ?? 0);
            $submenu_id = (int)($_POST['submenu_id'] ?? 0) ?: null;
            $nome = trim((string)($_POST['nome'] ?? ''));
            $icone = trim((string)($_POST['icone'] ?? ''));
            $rota = trim((string)($_POST['rota_acao'] ?? ''));
            $ordem = (int)($_POST['ordem'] ?? 0);
            $ativo = isset($_POST['ativo']);
            if ($menu_id <= 0 || $nome === '' || $rota === '') { set_flash('danger', 'Informe menu, nome e rota.'); redirecionar('menu.listar'); }
            if ($id) {
                MenuModel\atualizar_item($id, $submenu_id ? null : $menu_id, $submenu_id, $nome, $icone, $rota, $ordem, $ativo);
                redirecionar('menu.listar', ['menu_id' => $menu_id, 'submenu_id' => $submenu_id ?? 0, 'item_id' => $id]);
            } else {
                $novo = MenuModel\criar_item($submenu_id ? null : $menu_id, $submenu_id, $nome, $icone, $rota, $ordem, $ativo);
                redirecionar('menu.listar', ['menu_id' => $menu_id, 'submenu_id' => $submenu_id ?? 0, 'item_id' => $novo]);
            }
            break;
        case 'perfis_item':
            $item_id = (int)($_POST['item_id'] ?? 0);
            $menu_id = (int)($_POST['menu_id'] ?? 0);
            $submenu_id = (int)($_POST['submenu_id'] ?? 0);
            $perfil_ids = array_map('intval', (array)($_POST['perfil_ids'] ?? []));
            if ($item_id <= 0) { set_flash('danger', 'Selecione um item.'); redirecionar('menu.listar', ['menu_id' => $menu_id, 'submenu_id' => $submenu_id]); }
            MenuModel\salvar_perfis_do_item($item_id, $perfil_ids);
            redirecionar('menu.listar', ['menu_id' => $menu_id, 'submenu_id' => $submenu_id, 'item_id' => $item_id]);
            break;
        default:
            set_flash('danger', 'Tipo inválido.');
            redirecionar('menu.listar');
    }
}

function excluir(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $tipo = (string)($_POST['tipo'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $menu_id = (int)($_POST['menu_id'] ?? 0);
    $submenu_id = (int)($_POST['submenu_id'] ?? 0);
    if ($id <= 0) { set_flash('danger', 'ID inválido.'); redirecionar('menu.listar'); }
    switch ($tipo) {
        case 'menu':
            MenuModel\excluir_menu($id);
            set_flash('success', 'Menu excluído.');
            redirecionar('menu.listar');
            break;
        case 'submenu':
            MenuModel\excluir_submenu($id);
            set_flash('success', 'Submenu excluído.');
            redirecionar('menu.listar', ['menu_id' => $menu_id]);
            break;
        case 'item':
            MenuModel\excluir_item($id);
            set_flash('success', 'Item excluído.');
            redirecionar('menu.listar', ['menu_id' => $menu_id, 'submenu_id' => $submenu_id]);
            break;
        default:
            set_flash('danger', 'Tipo inválido.');
            redirecionar('menu.listar');
    }
}

function reordenar(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $tipo = (string)($_POST['tipo'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $dir = (string)($_POST['dir'] ?? '');
    if ($id <= 0 || !in_array($dir, ['up','down'], true)) { http_response_code(400); echo 'Parâmetros inválidos'; exit; }
    switch ($tipo) {
        case 'menu': MenuModel\mover_menu($id, $dir); break;
        case 'submenu': MenuModel\mover_submenu($id, $dir); break;
        case 'item': MenuModel\mover_item($id, $dir); break;
        default: http_response_code(400); echo 'Tipo inválido'; exit;
    }
    redirecionar('menu.listar');
}

function mover_item_ajax(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    header('Content-Type: application/json');
    $item_id = (int)($_POST['item_id'] ?? 0);
    $menu_id = (int)($_POST['menu_id'] ?? 0);
    $submenu_id = (int)($_POST['submenu_id'] ?? 0);
    $submenu_id = $submenu_id > 0 ? $submenu_id : null;
    if ($item_id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'item_id invalido']); return; }
    // Se vier destino submenu, alinhar o menu_id ao do submenu para evitar divergncias
    if ($submenu_id) {
        try {
            $pdo = \App\DB\pdo();
            $st = $pdo->prepare('SELECT menu_id FROM submenu WHERE id = :sid');
            $st->execute([':sid' => $submenu_id]);
            $mid = (int)($st->fetchColumn() ?: 0);
            if ($mid <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'submenu invalido']); return; }
            $menu_id = $mid; // garante coerncia com o submenu
        } catch (\Throwable $e) { /* continua com valor recebido */ }
    }
    if ($menu_id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'menu_id invalido']); return; }
    $ok = MenuModel\mover_item_para2($item_id, $menu_id, $submenu_id);
    if ($ok) echo json_encode(['ok'=>true]); else { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'falha update']); }
}

function mover_item(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    header('Content-Type: application/json');
    $item_id = (int)($_POST['item_id'] ?? 0);
    $menu_id = (int)($_POST['menu_id'] ?? 0);
    $submenu_id = (int)($_POST['submenu_id'] ?? 0);
    $submenu_id = $submenu_id > 0 ? $submenu_id : null;
    if ($item_id <= 0 || $menu_id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Parâmetros inválidos']); return; }
    $ok = MenuModel\mover_item_para2($item_id, $menu_id, $submenu_id);
    if ($ok) echo json_encode(['ok'=>true]); else { http_response_code(400); echo json_encode(['ok'=>false]); }
}

function mover_submenu_ajax(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    header('Content-Type: application/json');
    $submenu_id = (int)($_POST['submenu_id'] ?? 0);
    $menu_id = (int)($_POST['menu_id'] ?? 0);
    if ($submenu_id <= 0 || $menu_id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Parametros invalidos']); return; }
    $ok = MenuModel\mover_submenu_para($submenu_id, $menu_id);
    if ($ok) echo json_encode(['ok'=>true]); else { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'falha update']); }
}

function reordenar_nivel(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $menu_id = (int)($_POST['menu_id'] ?? 0);
    $tipo = (string)($_POST['tipo'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $dir = (string)($_POST['dir'] ?? '');
    if ($menu_id<=0 || $id<=0 || !in_array($tipo,['item','submenu'], true) || !in_array($dir,['up','down'], true)) {
        http_response_code(400); echo 'Parâmetros inválidos'; exit;
    }
    \App\Modelos\Menu\mover_nivel_menu($menu_id, $tipo, $id, $dir);
    redirecionar('menu.listar');
}
