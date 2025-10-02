<?php
declare(strict_types=1);

use function App\Lib\{redirecionar, set_flash, auditar_logout};
require_once __DIR__ . '/../modelos/Usuario.php';
require_once __DIR__ . '/../modelos/SenhaReset.php';
require_once __DIR__ . '/../modelos/Config.php';
require_once __DIR__ . '/../modelos/LoginLocalizacao.php';
use App\Modelos\Usuario as UsuarioModel;
use App\Modelos\SenhaReset as ResetModel;
use function App\Lib\Email\enviar as enviar_email;
use function App\Lib\CSRF\{input as csrf_input, validar as csrf_validar};
use function App\Lib\{bloqueado_por_tentativas, tentar_login, set_usuario_sessao};

// Helper de configuraçãoo: retorna o primeiro valor não-nulo dentre os escopos informados
function cfg_obter(string $chave, array $escopos = ['sistema','Variavel de Ambiente']): ?string {
    foreach ($escopos as $escopo) {
        try {
            $v = \App\Modelos\Config\obter($escopo, $chave);
            if ($v !== null && $v !== '') { return (string)$v; }
        } catch (\Throwable $e) {}
    }
    return null;
}

function login(): void {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: text/html; charset=UTF-8');

    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } catch (\Throwable $e) {}

    $flash = App\Lib\get_flash();
    // Corrige mensagens com acentuaçãoo (UTF-8) antes de qualquer fallback
    if (!$flash && !empty($_GET['manutencao'])) {
        $flash = ['tipo' => 'warning', 'mensagem' => 'Sistema em Manutenção.'];
    }
    if (!$flash && !empty($_GET['timeout'])) {
        $flash = ['tipo' => 'warning', 'mensagem' => 'Seu tempo de sessão acabou. Faça login novamente.'];
    }
    if (!$flash && !empty($_GET['manutencao'])) {
        $flash = ['tipo' => 'warning', 'mensagem' => 'Sistema em Manutenção.'];
    }
    if (!$flash && !empty($_GET['timeout'])) {
        $flash = ['tipo' => 'warning', 'mensagem' => 'Seu tempo de sessão acabou. Faça login novamente.'];
    }

    $email_prefill = $_SESSION['login_email'] ?? '';

    // Exige CAPTCHA apÃ³s 3 falhas consecutivas (sessão) para o e-mail prefill
    $mostrar_captcha = false; $captcha_pergunta = null;
    try {
        if (!empty($_SESSION['captcha_required'])) {
            $mostrar_captcha = true;
        } elseif ($email_prefill !== '') {
            $k = mb_strtolower(trim((string)$email_prefill));
            $falhasSess = (int)($_SESSION['login_falhas'][$k] ?? 0);
            if ($falhasSess >= 3) { $mostrar_captcha = true; }
        }
        if ($mostrar_captcha) {
            $a = random_int(1, 9); $b = random_int(1, 9);
            $_SESSION['captcha_resposta'] = (string)($a + $b);
            $_SESSION['captcha_required'] = true;
            $captcha_pergunta = "Quanto é $a + $b?";
        } else {
            unset($_SESSION['captcha_resposta'], $_SESSION['captcha_required']);
        }
    } catch (\Throwable $e) {}

    include __DIR__ . '/../visoes/autenticacao/login.php';
}

function entrar(): void {
    App\Lib\confirmar_post();
    csrf_validar();

    $email = trim((string)($_POST['email'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    if ($email === '' || $senha === '') {
        $_SESSION['login_email'] = $email;
        set_flash('danger', 'Informe e-mail e senha.');
        redirecionar('autenticacao.login');
    }

    if (bloqueado_por_tentativas($email)) {
        $_SESSION['login_email'] = $email;
        set_flash('warning', 'Muitas tentativas. Aguarde alguns minutos.');
        redirecionar('autenticacao.login');
    }

    // CAPTCHA por 3 falhas consecutivas (sessao)
    $emailKey = mb_strtolower(trim($email));
    $falhasSess = (int)($_SESSION['login_falhas'][$emailKey] ?? 0);
    if ($falhasSess >= 3 || !empty($_SESSION['captcha_required'])) {
        $resp = trim((string)($_POST['captcha'] ?? ''));
        if ($resp === '' || !hash_equals((string)($_SESSION['captcha_resposta'] ?? ''), $resp)) {
            $_SESSION['login_email'] = $email;
            $_SESSION['captcha_required'] = true;
            set_flash('warning', 'Resolva o CAPTCHA para continuar.');
            redirecionar('autenticacao.login');
        }
    }

    $res = tentar_login($email, $senha);
    if (!$res['ok']) {
        $_SESSION['login_email'] = $email;
        try {
            $_SESSION['login_falhas'][$emailKey] = $falhasSess + 1;
            if ($_SESSION['login_falhas'][$emailKey] >= 3) { $_SESSION['captcha_required'] = true; }
        } catch (\Throwable $e) {}
        set_flash('danger', $res['mensagem']);
        redirecionar('autenticacao.login');
    }

    $u = $res['usuario'];

    // Respeita modo Manutenção (apenas Administrador entra)
    $manut = cfg_obter('sistema_em_manutencao');
        $em_manutencao = false;
    if ($manut !== null) { $v = strtolower(trim($manut)); $em_manutencao = in_array($v, ['1','true','t','yes','sim','y'], true); }
    if ($em_manutencao && strtolower((string)($u['perfil_nome'] ?? '')) !== 'administrador') {
        $_SESSION['login_email'] = $email;
        set_flash('warning', 'Sistema em Manutenção.');
        redirecionar('autenticacao.login');
    }

    // Enforce localização se habilitado
    $forcaLoc = false;
    try {
        $vl = App\Modelos\Config\obter('Variavel de Ambiente', 'ForcaLocGeo');
        if ($vl !== null) { $vv = strtolower(trim((string)$vl)); $forcaLoc = in_array($vv, ['1','true','t','yes','sim','y','on','habilitado','enabled'], true); }
    } catch (\Throwable $e) {}

    if ($forcaLoc) {
        try { $nonce = bin2hex(random_bytes(16)); } catch (\Throwable $e) { $nonce = bin2hex((string)mt_rand()); }
        $_SESSION['login_pendente'] = [
            'usuario_id' => (int)$u['id'],
            'nonce' => $nonce,
            'criado_em' => time(),
        ];
        unset($_SESSION['captcha_resposta'], $_SESSION['captcha_required']);
        try { unset($_SESSION['login_falhas'][$emailKey]); } catch (\Throwable $e) {}
        set_flash('info', 'Autorize a localização para concluir o login.');
        redirecionar('autenticacao.localizacao');
    }

    // Seta sessão e presença (fluxo normal)
    set_usuario_sessao($u);
    unset($_SESSION['captcha_resposta'], $_SESSION['captcha_required']);
    try { unset($_SESSION['login_falhas'][$emailKey]); } catch (\Throwable $e) {}
    App\Lib\registrar_presenca();

    // PolÃ­tica de troca/aviso de senha (pÃ³s-login)
    $getEnvInt = function(string $key, int $def=0): int {
        try {
            $v = cfg_obter($key, ['Variavel de Ambiente']);            
            return (int)trim((string)($v ?? '0')) ?: $def;
        } catch (\Throwable $e) { return $def; }
    };
    $forceDays = $getEnvInt('forcaTrocaSenhaDias', 0);
    $warnDays  = $getEnvInt('aviso_troca_senha', 0);
    $troca = $u['senha_trocada_em'] ?? null;
    if ($troca === null || trim((string)$troca) === '') {
        $_SESSION['__force_change_pwd'] = true;
    } else if ($forceDays > 0) {
        $deadline = strtotime((string)$troca) + ($forceDays * 86400);
        if (time() >= $deadline) {
            $_SESSION['__force_change_pwd'] = true;
        } elseif ($warnDays > 0) {
            $daysLeft = (int)ceil(($deadline - time()) / 86400);
            if ($daysLeft > 0 && $daysLeft <= $warnDays) {
                $_SESSION['senha_aviso_dias'] = $daysLeft;
            }
        }
    }

    unset($_SESSION['login_email']);
    if (!empty($_SESSION['__force_change_pwd'])) {
        unset($_SESSION['__force_change_pwd']);
        set_flash('warning', 'Por seguranca, defina uma nova senha agora.');
        redirecionar('conta.meu');
    }
    redirecionar('dashboard.inicio');
}

function localizacao(): void {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: text/html; charset=UTF-8');
    $pend = $_SESSION['login_pendente'] ?? null;
    if (!$pend || empty($pend['nonce']) || empty($pend['usuario_id'])) {
        set_flash('warning', 'Sessão de login pendente não encontrada.');
        redirecionar('autenticacao.login');
    }
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/autenticacao/localizacao.php';
}

function finalizar(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $pend = $_SESSION['login_pendente'] ?? null;
    if (!$pend) { set_flash('warning','Sessão pendente expirada.'); redirecionar('autenticacao.login'); }

    $noncePost = (string)($_POST['nonce'] ?? '');
    if ($noncePost === '' || !hash_equals((string)$pend['nonce'], $noncePost)) {
        unset($_SESSION['login_pendente']);
        set_flash('danger', 'Fluxo inválido. Tente novamente.');
        redirecionar('autenticacao.login');
    }

    $ttl = 120; // segundos
    try { $t = App\Modelos\Config\obter('Variavel de Ambiente','ForcaLocGeo_TimeoutS'); if ($t !== null) { $ttl = max(5, (int)trim((string)$t)); } } catch (\Throwable $e) {}
    if (time() - (int)$pend['criado_em'] > $ttl) {
        unset($_SESSION['login_pendente']);
        set_flash('warning','Tempo esgotado para confirmar a localização.');
        redirecionar('autenticacao.login');
    }

    $perm = strtolower(trim((string)($_POST['perm'] ?? '')));
    $lat  = (string)($_POST['lat'] ?? '');
    $lon  = (string)($_POST['lon'] ?? '');
    $acc  = (string)($_POST['acc'] ?? '');
    $capt = (string)($_POST['capturado_em'] ?? '');

    if ($perm !== 'granted' || $lat === '' || $lon === '') {
        unset($_SESSION['login_pendente']);
        set_flash('danger','Localização não autorizada.');
        redirecionar('autenticacao.login');
    }

    $precMax = 0;
    try { $p = App\Modelos\Config\obter('Variavel de Ambiente','ForcaLocGeo_PrecisaoMaxM'); if ($p !== null) { $precMax = max(0, (int)trim((string)$p)); } } catch (\Throwable $e) {}
    if ($precMax > 0) {
        $accVal = (float)$acc;
        if ($accVal <= 0 || $accVal > $precMax) {
            unset($_SESSION['login_pendente']);
            set_flash('warning','Precisão insuficiente para confirmar a localização.');
            redirecionar('autenticacao.login');
        }
    }

    // Criar sessão do usuário
    $uid = (int)$pend['usuario_id'];
    $u = UsuarioModel\buscar($uid);
    if (!$u) {
        unset($_SESSION['login_pendente']);
        set_flash('danger', 'Usuário não encontrado.');
        redirecionar('autenticacao.login');
    }
    set_usuario_sessao($u);
    App\Lib\registrar_presenca();

    // Registrar localização
    try {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        \App\Modelos\LoginLocalizacao\registrar([
            'usuario_id' => $uid,
            'sessao_id' => session_id(),
            'login_em' => date('c'),
            'fonte' => 'device',
            'permissao' => 'granted',
            'latitude' => $lat,
            'longitude' => $lon,
            'precisao_m' => $acc,
            'ip' => $ip,
            'user_agent' => $ua,
            'capturado_em' => ($capt !== '' ? $capt : date('c')),
            'observacoes' => '',
        ]);
    } catch (\Throwable $e) { /* ignora falha de auditoria */ }

    unset($_SESSION['login_pendente']);

    // Política de troca/aviso de senha (pós-login), replicada do fluxo normal
    $getEnvInt = function(string $key, int $def=0): int {
        try {
            $v = cfg_obter($key, ['Variavel de Ambiente']);
            return (int)trim((string)($v ?? '0')) ?: $def;
        } catch (\Throwable $e) { return $def; }
    };
    $forceDays = $getEnvInt('forcaTrocaSenhaDias', 0);
    $warnDays  = $getEnvInt('aviso_troca_senha', 0);
    $troca = $u['senha_trocada_em'] ?? null;
    if ($troca === null || trim((string)$troca) === '') {
        $_SESSION['__force_change_pwd'] = true;
    } else if ($forceDays > 0) {
        $deadline = strtotime((string)$troca) + ($forceDays * 86400);
        if (time() >= $deadline) {
            $_SESSION['__force_change_pwd'] = true;
        } elseif ($warnDays > 0) {
            $daysLeft = (int)ceil(($deadline - time()) / 86400);
            if ($daysLeft > 0 && $daysLeft <= $warnDays) {
                $_SESSION['senha_aviso_dias'] = $daysLeft;
            }
        }
    }

    if (!empty($_SESSION['__force_change_pwd'])) {
        unset($_SESSION['__force_change_pwd']);
        set_flash('warning', 'Por seguranca, defina uma nova senha agora.');
        redirecionar('conta.meu');
    }
    redirecionar('dashboard.inicio');
}

function sair(): void {
    $uid = App\Lib\usuario_logado()['id'] ?? null;
    if ($uid) {
        try {
            $pdo = App\DB\pdo();
            $st = $pdo->prepare('DELETE FROM usuario_online WHERE usuario_id = :id');
            $st->execute([':id' => (int)$uid]);
        } catch (\Throwable $e) {}
    }
    auditar_logout('manual');
    session_destroy();
    session_start();
    App\Lib\set_flash('info', 'Sessão encerrada.');
    redirecionar('autenticacao.login');
}

function esqueci(): void {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: text/html; charset=UTF-8');
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/autenticacao/esqueci.php';
}

function enviar_reset(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $email = trim((string)($_POST['email'] ?? ''));
    if ($email === '') { App\Lib\set_flash('warning','Informe o e-mail.'); App\Lib\redirecionar('autenticacao.esqueci'); }
    $pdo = App\DB\pdo();
    $st = $pdo->prepare('SELECT id, nome, email, telefone FROM usuario WHERE lower(email)=lower(:e) AND ativo = true');
    $st->execute([':e' => $email]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $token = bin2hex(random_bytes(32));
        ResetModel\invalidar_antigos((int)$u['id']);
        ResetModel\criar((int)$u['id'], $token, 3600);
        $base = \App\Modelos\Config\obter('Variavel de Ambiente', 'urlSistema');
        $base = rtrim((string)$base, '/');
        if ($base === '') { $base = (App\Config\base_path() ?: '') . '/publico'; }
        $link = $base . '/index.php?acao=autenticacao.redefinir&token=' . urlencode($token);
        $nomeEmpresa = App\Modelos\Config\obter('Variavel de Ambiente','NomeEmpresa') ?? 'Smarto';
        $assunto = 'Recuperação de acesso - ' . $nomeEmpresa;
        $corpo = "Olá,\n\nRecebemos um pedido para redefinir sua senha.\n\nUse o link abaixo dentro de 1 hora:\n" . $link . "\n\nSe você não solicitou, ignore este e-mail.";
        // Corrige assunto e corpo com acentuaçãoo correta (UTF-8)
        $assunto = 'Recuperação de acesso - ' . $nomeEmpresa;
        $corpo = "Olá,\n\nRecebemos um pedido para redefinir sua senha.\n\nUse o link abaixo dentro de 1 hora:\n" . $link . "\n\nSe você não solicitou, ignore este e-mail.";
        // Ajuste de assunto e corpo com acentuaçãoo correta (UTF-8)
        $assunto = 'Recuperação de acesso - ' . $nomeEmpresa;
        $corpo = "Olá,\n\nRecebemos um pedido para redefinir sua senha.\n\nUse o link abaixo dentro de 1 hora:\n" . $link . "\n\nSe você não solicitou, ignore este e-mail.";
        $cfgEmail = [
            'from_email'  => (string)(App\Modelos\Config\obter('email','from_email') ?? ''),
            'from_name'   => (string)(App\Modelos\Config\obter('email','from_name') ?? ''),
            'smtp_host'   => (string)(App\Modelos\Config\obter('email','smtp_host') ?? ''),
            'smtp_port'   => (int)(App\Modelos\Config\obter('email','smtp_port') ?? 587),
            'smtp_secure' => (string)(App\Modelos\Config\obter('email','smtp_secure') ?? 'tls'),
            'smtp_user'   => (string)(App\Modelos\Config\obter('email','smtp_user') ?? ''),
            'smtp_pass'   => (string)(App\Modelos\Config\obter('email','smtp_pass') ?? ''),
        ];
        $erro = null;
        $ok = \App\Lib\Email\enviar_email_smtp($cfgEmail, (string)$u['email'], $assunto, $corpo, $erro);
        if (!$ok) { enviar_email((string)$u['email'], $assunto, $corpo, $erro); }
        App\Lib\auditar_acao('senha.reset.enviar', ['email'=>$email, 'ok'=> $erro===null, 'erro'=>$erro]);
    } else {
        App\Lib\auditar_acao('senha.reset.enviar', ['email'=>$email, 'ok'=> false, 'motivo'=>'usuario_nao_encontrado']);
    }
    App\Lib\set_flash('success', 'Se o e-mail existir e estiver ativo, enviaremos um link de recuperação.');
    App\Lib\set_flash('success', 'Se o e-mail existir e estiver ativo, enviaremos um link de recuperação.');
    
    App\Lib\redirecionar('autenticacao.login');
}

function redefinir(): void {
    $token = (string)($_GET['token'] ?? '');
    $r = $token ? ResetModel\buscar_por_token($token) : null;
    $valido = $r && empty($r['usado_em']) && (strtotime((string)$r['expira_em']) > time());
    if (!$valido) {
        App\Lib\set_flash('danger', 'Link inválido ou expirado.');
        App\Lib\redirecionar('autenticacao.login');
    }
    $flash = App\Lib\get_flash();
    header('Content-Type: text/html; charset=UTF-8');
    include __DIR__ . '/../visoes/autenticacao/redefinir.php';
}

function redefinir_salvar(): void {
    App\Lib\confirmar_post();
    csrf_validar();
    $token = (string)($_POST['token'] ?? '');
    $senha = (string)($_POST['senha'] ?? '');
    $senha2= (string)($_POST['senha2'] ?? '');
    $r = $token ? ResetModel\buscar_por_token($token) : null;
    $valido = $r && empty($r['usado_em']) && (strtotime((string)$r['expira_em']) > time());
    if (!$valido) { App\Lib\set_flash('danger','Link invÃ¡lido ou expirado.'); App\Lib\redirecionar('autenticacao.login'); }
    if ($senha === '' || $senha !== $senha2) {
        App\Lib\set_flash('danger','Informe e confirme a nova senha.');
        App\Lib\redirecionar('autenticacao.redefinir', ['token'=>$token]);
    }
    if (mb_strlen($senha) > 14) {
        App\Lib\set_flash('danger','Senha deve ter no máximo 14 caracteres.');
        App\Lib\redirecionar('autenticacao.redefinir', ['token'=>$token]);
    }
    // Polí­tica de senha forte (Variavel de Ambiente: senhaForte)
    try {
        $v = cfg_obter('senhaForte', ['Variavel de Ambiente']);
        
        $senhaForte = false;
        if ($v !== null) { $vv = strtolower(trim((string)$v)); $senhaForte = in_array($vv, ['1','true','t','yes','sim','y','on','habilitado','enabled'], true); }
        if ($senhaForte) {
            $len = mb_strlen($senha);
            $ok = ($len >= 8 && $len <= 14) && preg_match('/[A-Z]/', $senha) && preg_match('/[a-z]/', $senha) && preg_match('/[^A-Za-z0-9]/', $senha);
            if (!$ok) {
                App\Lib\set_flash('danger','Senha forte obrigatória: 8-14, com maiúscula, minúscula e caractere especial.');
                App\Lib\redirecionar('autenticacao.redefinir', ['token'=>$token]);
            }
            // Não pode ser igual Ã  anterior
            $old = (string)((UsuarioModel\buscar((int)$r['usuario_id'])['senha_hash'] ?? ''));
            if ($old !== '' && password_verify($senha, $old)) {
                App\Lib\set_flash('danger','A nova senha não pode ser igual à anterior.');
                App\Lib\redirecionar('autenticacao.redefinir', ['token'=>$token]);
            }
        }
    } catch (\Throwable $e) {}
    $uid = (int)$r['usuario_id'];
    $u = UsuarioModel\buscar($uid) ?: [];
    App\Modelos\Usuario\atualizar_telefone_e_senha($uid, (string)($u['telefone'] ?? ''), $senha);
    ResetModel\marcar_usado($token);
    
    App\Lib\redirecionar('autenticacao.login');
}
