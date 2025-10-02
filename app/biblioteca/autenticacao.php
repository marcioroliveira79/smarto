<?php
declare(strict_types=1);

namespace App\Lib;

use App\DB;
use PDO;

const MAX_TENTATIVAS = 5; // por 15 minutos
const JANELA_MINUTOS = 15;
const CAPTCHA_TENTATIVAS = 3; // após 3 falhas, exigir CAPTCHA

function registrar_tentativa(string $email, bool $sucesso): void {
    try {
        $pdo = DB\pdo();
        $stmt = $pdo->prepare('INSERT INTO login_tentativa (email, ip, sucesso) VALUES (:email, :ip, :sucesso)');
        $stmt->execute([
            ':email' => mb_strtolower(trim($email)),
            ':ip' => obter_ip(),
            ':sucesso' => $sucesso,
        ]);
    } catch (\Throwable $e) {
        // ignora
    }
}

function bloqueado_por_tentativas(string $email): bool {
    $falhas = falhas_recentes($email);
    return $falhas >= MAX_TENTATIVAS;
}

function falhas_recentes(string $email): int {
    $pdo = DB\pdo();
    $stmt = $pdo->prepare('SELECT COUNT(*)
        FROM login_tentativa
        WHERE email = :email AND sucesso = false AND criado_em > (now() - CAST(:janela AS interval))');
    $janela = JANELA_MINUTOS . ' minutes';
    $stmt->execute([':email' => mb_strtolower(trim($email)), ':janela' => $janela]);
    return (int)$stmt->fetchColumn();
}

function requer_captcha(string $email): bool {
    return falhas_recentes($email) >= CAPTCHA_TENTATIVAS;
}

function tentar_login(string $email, string $senha): array {
    $pdo = DB\pdo();
    $stmt = $pdo->prepare('SELECT u.*, p.nome AS perfil_nome
        FROM usuario u
        JOIN perfil p ON p.id = u.perfil_id
        WHERE lower(u.email) = lower(:email) AND u.ativo = true');
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        registrar_tentativa($email, false);
        auditar_acao('login.falha', ['email' => $email]);
        return ['ok' => false, 'mensagem' => 'Credenciais inválidas.'];
    }

    $hash = (string)$usuario['senha_hash'];
    $valido = password_verify($senha, $hash);
    if (!$valido && strlen($hash) < 40) { // fallback mínimo se semente usar texto (não recomendado)
        $valido = hash_equals($hash, $senha);
        if ($valido) {
            // atualiza para hash forte
            $novo = password_hash($senha, PASSWORD_BCRYPT);
            $up = $pdo->prepare('UPDATE usuario SET senha_hash = :h, atualizado_em = now() WHERE id = :id');
            $up->execute([':h' => $novo, ':id' => (int)$usuario['id']]);
            $usuario['senha_hash'] = $novo;
        }
    }

    if (!$valido) {
        registrar_tentativa($email, false);
        auditar_acao('login.falha', ['email' => $email]);
        return ['ok' => false, 'mensagem' => 'Credenciais inválidas.'];
    }

    registrar_tentativa($email, true);
    auditar_acao('login.sucesso', ['usuario_id' => (int)$usuario['id']]);
    return ['ok' => true, 'usuario' => $usuario];
}

function set_usuario_sessao(array $usuario): void {
    $_SESSION['usuario'] = [
        'id' => (int)$usuario['id'],
        'nome' => (string)$usuario['nome'],
        'email' => (string)$usuario['email'],
        'perfil_id' => (int)$usuario['perfil_id'],
        'perfil_nome' => (string)$usuario['perfil_nome'],
        'login_ts' => time(),
    ];
}

function registrar_presenca(): void {
    $u = usuario_logado();
    if (!$u) return;
    // Se sistema em manutenção e usuário não for Administrador, não registra presença
    try {
        $manut = \App\Modelos\Config\obter('sistema', 'sistema_em_manutencao');
        if ($manut === null) { $manut = \App\Modelos\Config\obter('Variavel de Ambiente', 'sistema_em_manutencao'); }        
        $em_manutencao = false;
        if ($manut !== null) { $v = strtolower(trim($manut)); $em_manutencao = in_array($v, ['1','true','t','yes','sim','y'], true); }
        $perfil = strtolower((string)($u['perfil_nome'] ?? ''));
        if ($em_manutencao && $perfil !== 'administrador') {
            // Remover eventual registro e sair
            try {
                $pdo = DB\pdo();
                $st = $pdo->prepare('DELETE FROM usuario_online WHERE usuario_id = :id');
                $st->execute([':id' => (int)$u['id']]);
            } catch (\Throwable $e) { /* ignora */ }
            return;
        }
    } catch (\Throwable $e) { /* ignora e segue */ }
    $pdo = DB\pdo();
    $stmt = $pdo->prepare('INSERT INTO usuario_online (usuario_id, ip, user_agent, ultimo_sinal)
        VALUES (:id, :ip, :ua, now())
        ON CONFLICT (usuario_id) DO UPDATE
        SET ultimo_sinal = EXCLUDED.ultimo_sinal, ip = EXCLUDED.ip, user_agent = EXCLUDED.user_agent');
    $stmt->execute([':id' => (int)$u['id'], ':ip' => obter_ip(), ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
}



