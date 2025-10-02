<?php
declare(strict_types=1);

use function App\Lib\{set_flash, redirecionar};

require_once __DIR__ . '/../modelos/Tema.php';
require_once __DIR__ . '/../modelos/Config.php';

function listar(): void {
    $temas = App\Modelos\Tema\listar();
    $ativo = App\Modelos\Config\obter('Variavel de Ambiente','tema_ativo_id');
    $ativo_id = $ativo !== null ? (int)trim((string)$ativo) : 0;
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/tema/listar.php';
}

function salvar(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim((string)($_POST['nome'] ?? ''));
    $cores = [
        'nav' => (string)($_POST['nav'] ?? '#0d6efd'),
        'action' => (string)($_POST['action'] ?? '#0d6efd'),
        'text_accent' => (string)($_POST['text_accent'] ?? '#0d6efd'),
        'back' => (string)($_POST['back'] ?? '#6c757d'),
        'delete' => (string)($_POST['delete'] ?? '#dc3545'),
    ];
    if ($nome === '') { set_flash('danger','Informe o nome do tema.'); redirecionar('tema.listar'); }
    if ($id > 0) { App\Modelos\Tema\atualizar($id, $nome, $cores); set_flash('success','Tema atualizado.'); }
    else { $id = App\Modelos\Tema\criar($nome, $cores); set_flash('success','Tema criado.'); }
    redirecionar('tema.listar');
}

function aplicar(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0 || !App\Modelos\Tema\buscar($id)) { set_flash('danger','Tema inválido.'); redirecionar('tema.listar'); }
    $uid = App\Lib\usuario_logado()['id'] ?? null;
    // Persistir como Variavel de Ambiente
    $st = App\DB\pdo()->prepare('SELECT id FROM config_sistema WHERE lower(processo)=lower(:p) AND lower(chave)=lower(:c)');
    $st->execute([':p'=>'Variavel de Ambiente', ':c'=>'tema_ativo_id']);
    $cfgId = $st->fetchColumn();
    App\Modelos\Config\salvar($cfgId ? (int)$cfgId : null, 'Variavel de Ambiente', 'tema_ativo_id', (string)$id, 'int', 'ID do tema ativo', (int)$uid);
    set_flash('success','Tema aplicado.');
    redirecionar('tema.listar');
}

function excluir(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { set_flash('danger','Tema inválido.'); redirecionar('tema.listar'); }
    App\Modelos\Tema\excluir($id);
    set_flash('success','Tema excluído.');
    redirecionar('tema.listar');
}

