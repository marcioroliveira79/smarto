<?php
declare(strict_types=1);

use App\Modelos\Perfil as PerfilModel;
use function App\Lib\{redirecionar, set_flash};
use function App\Lib\CSRF\{input as csrf_input, validar as csrf_validar};

require_once __DIR__ . '/../modelos/Perfil.php';

function listar(): void {
    $perfis = PerfilModel\listar();
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/perfil/listar.php';
}

function novo(): void {
    $perfil = null;
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/perfil/formulario.php';
}

function editar(): void {
    $id = (int)($_GET['id'] ?? 0);
    $perfil = PerfilModel\buscar($id);
    if (!$perfil) {
        http_response_code(404);
        $mensagem = 'Perfil não encontrado.';
        include __DIR__ . '/../visoes/erros/nao_encontrado.php';
        return;
    }
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/perfil/formulario.php';
}

function salvar(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim((string)($_POST['nome'] ?? ''));
    $descricao = trim((string)($_POST['descricao'] ?? ''));
    $ativo = isset($_POST['ativo']) ? true : false;
    if ($nome === '') {
        set_flash('danger', 'Informe o nome.');
        if ($id) redirecionar('perfil.editar', ['id' => $id]);
        redirecionar('perfil.novo');
    }
    if ($id) {
        PerfilModel\atualizar($id, $nome, $descricao, $ativo);
        set_flash('success', 'Perfil atualizado.');
        redirecionar('perfil.editar', ['id' => $id]);
    } else {
        $novoId = PerfilModel\criar($nome, $descricao, $ativo);
        set_flash('success', 'Perfil criado.');
        redirecionar('perfil.editar', ['id' => $novoId]);
    }
}

function excluir(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        set_flash('danger', 'ID invÃ¡lido.');
        redirecionar('perfil.listar');
    }
    if (PerfilModel\excluir($id)) {
        set_flash('success', 'Perfil excluÃ­do.');
    } else {
        set_flash('danger', 'NÃ£o foi possÃ­vel excluir.');
    }
    redirecionar('perfil.listar');
}


