<?php
declare(strict_types=1);

use function App\Lib\{usuario_logado, redirecionar, set_flash};
use function App\Lib\CSRF\{input as csrf_input, validar as csrf_validar};

require_once __DIR__ . '/../modelos/Usuario.php';
require_once __DIR__ . '/../modelos/Config.php';
require_once __DIR__ . '/../modelos/Perfil.php';

function meu(): void {
    $u = usuario_logado();
    if (!$u) { redirecionar('autenticacao.login'); }

    $usuario = App\Modelos\Usuario\buscar((int)$u['id']);
    if (!$usuario) {
        http_response_code(404);
        $mensagem = 'Usuário não encontrado.';
        include __DIR__ . '/../visoes/erros/nao_encontrado.php';
        return;
    }
    // Buscar nome do perfil
    $perfil_nome = '';
    if (isset($usuario['perfil_id'])) {
        $perfil = \App\Modelos\Perfil\buscar((int)$usuario['perfil_id']);
        $perfil_nome = $perfil['nome'] ?? '';
    }
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/conta/meu.php';
}

function salvar(): void {
    App\Lib\confirmar_post();
    csrf_validar();

    $u = usuario_logado();
    if (!$u) { redirecionar('autenticacao.login'); }
    $id = (int)$u['id'];

    $telefone = trim((string)($_POST['telefone'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    $senha2 = (string)($_POST['senha2'] ?? '');

    if ($telefone === '') {
        set_flash('danger', 'Informe o telefone.');
        redirecionar('conta.meu');
    }
    if ($senha !== '' && $senha !== $senha2) {
        set_flash('danger', 'As senhas não conferem.');
        redirecionar('conta.meu');
    }
    if ($senha !== '' && mb_strlen($senha) > 14) {
        set_flash('danger', 'Senha deve ter no máximo 14 caracteres.');
        redirecionar('conta.meu');
    }

    if (App\Modelos\Usuario\telefone_existe($telefone, $id)) {
        set_flash('danger', 'Telefone já cadastrado para outro usuário.');
        redirecionar('conta.meu');
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
                    redirecionar('conta.meu');
                }
                $uAtual = \App\Modelos\Usuario\buscar($id) ?: [];
                $old = (string)($uAtual['senha_hash'] ?? '');
                if ($old !== '' && password_verify($senha, $old)) {
                    set_flash('danger','A nova senha não pode ser igual à anterior.');
                    redirecionar('conta.meu');
                }
            }
        } catch (\Throwable $e) {}
    }

    $ok = App\Modelos\Usuario\atualizar_telefone_e_senha($id, $telefone, $senha !== '' ? $senha : null);
    if ($ok) {
        set_flash('success', 'Dados atualizados.');
    } else {
        set_flash('danger', 'Não foi possível atualizar.');
    }
    redirecionar('conta.meu');
}

