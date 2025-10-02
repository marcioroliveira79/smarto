<?php
declare(strict_types=1);

use App\Modelos\Usuario as UsuarioModel;
use App\Modelos\Perfil as PerfilModel;
use function App\Lib\{redirecionar, set_flash};
use function App\Lib\CSRF\{input as csrf_input, validar as csrf_validar};

require_once __DIR__ . '/../modelos/Usuario.php';
require_once __DIR__ . '/../modelos/Perfil.php';
require_once __DIR__ . '/../modelos/Config.php';

function listar(): void {
    $usuarios = UsuarioModel\listar();
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/usuario/listar.php';
}

function novo(): void {
    $perfis = PerfilModel\listar_ativos();
    $usuario = null;
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/usuario/formulario.php';
}

function editar(): void {
    $id = (int)($_GET['id'] ?? 0);
    $usuario = UsuarioModel\buscar($id);
    if (!$usuario) {
        http_response_code(404);
        $mensagem = 'Usuário não encontrado.';
        include __DIR__ . '/../visoes/erros/nao_encontrado.php';
        return;
    }
    $perfis = PerfilModel\listar_ativos();
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/usuario/formulario.php';
}

function salvar(): void {
    App\Lib\confirmar_post();
    csrf_validar();

    $id = (int)($_POST['id'] ?? 0);
    $nome = trim((string)($_POST['nome'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $telefone = trim((string)($_POST['telefone'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    $senha2 = (string)($_POST['senha2'] ?? '');
    $perfil_id = (int)($_POST['perfil_id'] ?? 0);
    $ativo = isset($_POST['ativo']) ? true : false;

    if ($nome === '' || $email === '' || $perfil_id <= 0 || $telefone === '') {
        set_flash('danger', 'Preencha os campos obrigatórios (incluindo telefone).');
        if ($id) redirecionar('usuario.editar', ['id' => $id]);
        redirecionar('usuario.novo');
    }
    if (!$id && $senha === '') {
        set_flash('danger', 'Senha obrigatória para novo usuário.');
        redirecionar('usuario.novo');
    }
    if ($senha !== '' && $senha !== $senha2) {
        set_flash('danger', 'As senhas não conferem.');
        if ($id) redirecionar('usuario.editar', ['id' => $id]);
        redirecionar('usuario.novo');
    }
    if ($senha !== '' && mb_strlen($senha) > 14) {
        set_flash('danger', 'Senha deve ter no máximo 14 caracteres.');
        if ($id) redirecionar('usuario.editar', ['id' => $id]);
        redirecionar('usuario.novo');
    }

    if (UsuarioModel\telefone_existe($telefone, $id ?: null)) {
        set_flash('danger', 'Telefone já cadastrado para outro usuário.');
        if ($id) redirecionar('usuario.editar', ['id' => $id]);
        redirecionar('usuario.novo');
    }

    // Política de senha forte (quando habilitada) e não repetir senha anterior
    if ($senha !== '') {
        try {
            $v = \App\Modelos\Config\obter('Variavel de Ambiente','senhaForte');
            $senhaForte = false;
            if ($v !== null) {
                $vv = strtolower(trim((string)$v));
                $senhaForte = in_array($vv, ['1','true','t','yes','sim','y','on','habilitado','enabled'], true);
            }
            if ($senhaForte) {
                $len = mb_strlen($senha);
                $okStrong = ($len >= 8 && $len <= 14)
                    && preg_match('/[A-Z]/', $senha)
                    && preg_match('/[a-z]/', $senha)
                    && preg_match('/[^A-Za-z0-9]/', $senha);
                if (!$okStrong) {
                    set_flash('danger','Senha forte obrigatória: 8-14, com maiúscula, minúscula e caractere especial.');
                    if ($id) redirecionar('usuario.editar', ['id' => $id]);
                    redirecionar('usuario.novo');
                }
                if ($id) {
                    $uAtual = UsuarioModel\buscar($id) ?: [];
                    $old = (string)($uAtual['senha_hash'] ?? '');
                    if ($old !== '' && password_verify($senha, $old)) {
                        set_flash('danger','A nova senha não pode ser igual à anterior.');
                        redirecionar('usuario.editar', ['id' => $id]);
                    }
                }
            }
        } catch (\Throwable $e) {}
    }

    if ($id) {
        UsuarioModel\atualizar($id, $nome, $email, $telefone, $perfil_id, $ativo, $senha ?: null);
        set_flash('success', 'Usuário atualizado com sucesso.');
        redirecionar('usuario.editar', ['id' => $id]);
    } else {
        $novoId = UsuarioModel\criar($nome, $email, $perfil_id, $ativo, $senha, $telefone);
        set_flash('success', 'Usuário criado com sucesso.');
        redirecionar('usuario.editar', ['id' => $novoId]);
    }
}

function excluir(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        set_flash('danger', 'ID inválido.');
        redirecionar('usuario.listar');
    }
    if (UsuarioModel\excluir($id)) {
        set_flash('success', 'Usuário excluído.');
    } else {
        set_flash('danger', 'Não foi possível excluir.');
    }
    redirecionar('usuario.listar');
}

