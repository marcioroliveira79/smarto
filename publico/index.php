<?php
error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', '0');
// Roteador simples baseado no parametro GET "acao"

// Define diretório dedicado para arquivos de sessão antes de iniciar a sessão
// Usa c:\laragon7\www\smarto\session (../session a partir deste arquivo)
try {
    $sessDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'session';
    if (!is_dir($sessDir)) { @mkdir($sessDir, 0770, true); }
    if (is_dir($sessDir) && @is_writable($sessDir)) { session_save_path($sessDir); }
} catch (\Throwable $e) { /* segue com padrão do PHP */ }

@session_start();

require_once __DIR__ . '/../app/config/env.php';
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/biblioteca/util.php';
require_once __DIR__ . '/../app/biblioteca/csrf.php';
require_once __DIR__ . '/../app/biblioteca/autenticacao.php';
require_once __DIR__ . '/../app/biblioteca/email.php';
require_once __DIR__ . '/../app/biblioteca/acl.php';
require_once __DIR__ . '/../app/biblioteca/tema.php';
require_once __DIR__ . '/../app/modelos/Config.php';

use function App\Lib\{require_login, redirecionar, usuario_logado, auditar_acao};

$acao = $_GET['acao'] ?? null;

// Se nÃ£o autenticado, enviar para login, exceto aÃ§Ãµes pÃºblicas
$acoes_publicas = [
    'autenticacao.login',
    'autenticacao.entrar',
    'autenticacao.esqueci',
    'autenticacao.enviar_reset',
    'autenticacao.redefinir',
    'autenticacao.redefinir_salvar',
    'autenticacao.localizacao',
    'autenticacao.finalizar',
    // Rota de utilitÃ¡rio para aplicar SQL em ambiente local
    'dev.aplicar_sql',
];

if (!usuario_logado() && !in_array($acao, $acoes_publicas, true)) {
    $acao = 'autenticacao.login';
}

if (!$acao) {
    $acao = usuario_logado() ? 'dashboard.inicio' : 'autenticacao.login';
}

// Modo manutenÃ§Ã£o: se ativado, derruba sessÃµes de nÃ£o administradores e impede login
$manutencaoVal = \App\Modelos\Config\obter('sistema', 'sistema_em_manutencao');
// Fallbacks para outras chaves comuns
if ($manutencaoVal === null) { $manutencaoVal = \App\Modelos\Config\obter('Variavel de Ambiente', 'sistema_em_manutencao'); }

$em_manutencao = false;
if ($manutencaoVal !== null) {
    $v = strtolower(trim($manutencaoVal));
    $em_manutencao = in_array($v, ['1','true','t','yes','sim','y'], true);
}
if ($em_manutencao) {
    $u = usuario_logado();
    $isAdmin = is_array($u) && (strtolower((string)($u['perfil_nome'] ?? '')) === 'administrador');
    // Limpa imediatamente a presenÃ§a de todos os nÃ£o administradores
    try {
        $pdo = \App\DB\pdo();
        $pdo->exec("DELETE FROM usuario_online WHERE usuario_id IN (SELECT u.id FROM usuario u JOIN perfil p ON p.id = u.perfil_id WHERE lower(p.nome) <> 'administrador')");
    } catch (\Throwable $e) { /* ignora */ }
    if ($u && !$isAdmin) {
        try {
            $pdo = \App\DB\pdo();
            $st = $pdo->prepare('DELETE FROM usuario_online WHERE usuario_id = :id');
            $st->execute([':id' => (int)$u['id']]);
        } catch (\Throwable $e) { /* ignora */ }
        \App\Lib\auditar_logout('manutencao', ['usuario_id' => (int)$u['id']]);
        session_destroy();
        session_start();
        \App\Lib\set_flash('warning', 'Sistema em ManutenÃ§Ã£o.');
        \App\Lib\redirecionar('autenticacao.login', ['manutencao' => 1, '_ts' => (string)time()]);
    }
    // Para requisiÃ§Ãµes nÃ£o autenticadas: bloquear tela de login sÃ³ no controlador apÃ³s checar credenciais (somente admins entram)
}

// Tempo mÃ¡ximo de sessÃ£o (sistema/tempoMaximoSessaoSegundos)
try {
    $ttlVal = \App\Modelos\Config\obter('sistema', 'tempoMaximoSessaoSegundos');
    if ($ttlVal === null) {
        try {
            $pdo = \App\DB\pdo();
            $st = $pdo->prepare('SELECT valor FROM config_sistema WHERE lower(chave) = lower(:c) ORDER BY atualizado_em DESC NULLS LAST, id DESC LIMIT 1');
            $st->execute([':c' => 'tempoMaximoSessaoSegundos']);
            $v = $st->fetchColumn();
            if ($v !== false) { $ttlVal = (string)$v; }
        } catch (\Throwable $e) { /* ignora */ }
    }
    $ttl = (int)($ttlVal !== null ? trim((string)$ttlVal) : 0);
    if ($ttl > 0) {
        $u = usuario_logado();
        if ($u) {
            $now = time();
            if (!isset($_SESSION['usuario']['login_ts']) || (int)$_SESSION['usuario']['login_ts'] <= 0) {
                // Inicializa carimbo
                $_SESSION['usuario']['login_ts'] = $now;
            }
            $elapsed = $now - (int)$_SESSION['usuario']['login_ts'];
            if ($elapsed >= $ttl) {
                try {
                    $pdo = \App\DB\pdo();
                    $st = $pdo->prepare('DELETE FROM usuario_online WHERE usuario_id = :id');
                    $st->execute([':id' => (int)$u['id']]);
                } catch (\Throwable $e) { /* ignora */ }
                \App\Lib\auditar_logout('timeout', [ 'ttl' => (int)$ttl, 'elapsed' => (int)$elapsed ]);
                session_destroy();
                session_start();
                \App\Lib\set_flash('warning', 'Seu tempo de sessão acabou. Faça login novamente.');
                \App\Lib\redirecionar('autenticacao.login', ['timeout' => 1, '_ts' => (string)time()]);
            } else {
                // RenovaÃ§Ã£o deslizante por atividade
                // noop: manter login_ts original (sem renovacao deslizante)
            }
        }
    }
} catch (\Throwable $e) { /* ignora */ }

// Mapa de rotas -> controladores
$rotas = [
    // AutenticaÃ§Ã£o
    'autenticacao.login' => ['arquivo' => __DIR__ . '/../app/controladores/autenticacao.php', 'func' => 'login'],
    'autenticacao.entrar' => ['arquivo' => __DIR__ . '/../app/controladores/autenticacao.php', 'func' => 'entrar'],
    'autenticacao.esqueci' => ['arquivo' => __DIR__ . '/../app/controladores/autenticacao.php', 'func' => 'esqueci'],
    'autenticacao.enviar_reset' => ['arquivo' => __DIR__ . '/../app/controladores/autenticacao.php', 'func' => 'enviar_reset'],
    'autenticacao.redefinir' => ['arquivo' => __DIR__ . '/../app/controladores/autenticacao.php', 'func' => 'redefinir'],
    'autenticacao.redefinir_salvar' => ['arquivo' => __DIR__ . '/../app/controladores/autenticacao.php', 'func' => 'redefinir_salvar'],
    'autenticacao.localizacao' => ['arquivo' => __DIR__ . '/../app/controladores/autenticacao.php', 'func' => 'localizacao'],
    'autenticacao.finalizar'    => ['arquivo' => __DIR__ . '/../app/controladores/autenticacao.php', 'func' => 'finalizar'],
    'autenticacao.sair' => ['arquivo' => __DIR__ . '/../app/controladores/autenticacao.php', 'func' => 'sair'],

    // Dashboard e monitoramento
    'dashboard.inicio' => ['arquivo' => __DIR__ . '/../app/controladores/dashboard.php', 'func' => 'inicio'],
    'monitor.ping' => ['arquivo' => __DIR__ . '/../app/controladores/monitor.php', 'func' => 'ping'],
    'monitor.online' => ['arquivo' => __DIR__ . '/../app/controladores/monitor.php', 'func' => 'online'],
    'monitor.logins_geo' => ['arquivo' => __DIR__ . '/../app/controladores/monitor.php', 'func' => 'logins_geo'],

    // UsuÃ¡rios
    'usuario.listar' => ['arquivo' => __DIR__ . '/../app/controladores/usuario.php', 'func' => 'listar'],
    'usuario.novo' => ['arquivo' => __DIR__ . '/../app/controladores/usuario.php', 'func' => 'novo'],
    'usuario.editar' => ['arquivo' => __DIR__ . '/../app/controladores/usuario.php', 'func' => 'editar'],
    'usuario.salvar' => ['arquivo' => __DIR__ . '/../app/controladores/usuario.php', 'func' => 'salvar'],
    'usuario.excluir' => ['arquivo' => __DIR__ . '/../app/controladores/usuario.php', 'func' => 'excluir'],

    // Perfis
    'perfil.listar' => ['arquivo' => __DIR__ . '/../app/controladores/perfil.php', 'func' => 'listar'],
    'perfil.novo' => ['arquivo' => __DIR__ . '/../app/controladores/perfil.php', 'func' => 'novo'],
    'perfil.editar' => ['arquivo' => __DIR__ . '/../app/controladores/perfil.php', 'func' => 'editar'],
    'perfil.salvar' => ['arquivo' => __DIR__ . '/../app/controladores/perfil.php', 'func' => 'salvar'],
    'perfil.excluir' => ['arquivo' => __DIR__ . '/../app/controladores/perfil.php', 'func' => 'excluir'],

    // Dev (somente local/admin)
    'dev.aplicar_sql' => ['arquivo' => __DIR__ . '/../app/controladores/dev.php', 'func' => 'aplicar_sql'],

    // Menus e Itens
    'menu.listar' => ['arquivo' => __DIR__ . '/../app/controladores/menu.php', 'func' => 'listar'],
    'menu.salvar' => ['arquivo' => __DIR__ . '/../app/controladores/menu.php', 'func' => 'salvar'],
    'menu.excluir' => ['arquivo' => __DIR__ . '/../app/controladores/menu.php', 'func' => 'excluir'],
    'menu.reordenar' => ['arquivo' => __DIR__ . '/../app/controladores/menu.php', 'func' => 'reordenar'],
    'menu.reordenar_nivel' => ['arquivo' => __DIR__ . '/../app/controladores/menu.php', 'func' => 'reordenar_nivel'],
    'menu.mover_item' => ['arquivo' => __DIR__ . '/../app/controladores/menu.php', 'func' => 'mover_item_ajax'],
    'menu.mover_submenu' => ['arquivo' => __DIR__ . '/../app/controladores/menu.php', 'func' => 'mover_submenu_ajax'],
    'menu.nav' => ['arquivo' => __DIR__ . '/../app/controladores/menu.php', 'func' => 'nav'],

    // Backup
    'backup.painel' => ['arquivo' => __DIR__ . '/../app/controladores/backup.php', 'func' => 'painel'],
    'backup.gerar' => ['arquivo' => __DIR__ . '/../app/controladores/backup.php', 'func' => 'gerar'],
    'backup.baixar' => ['arquivo' => __DIR__ . '/../app/controladores/backup.php', 'func' => 'baixar'],
    'backup.excluir' => ['arquivo' => __DIR__ . '/../app/controladores/backup.php', 'func' => 'excluir'],

    // Conta do usuÃ¡rio (self-service)
    'conta.meu' => ['arquivo' => __DIR__ . '/../app/controladores/conta.php', 'func' => 'meu'],
    'conta.salvar' => ['arquivo' => __DIR__ . '/../app/controladores/conta.php', 'func' => 'salvar'],

    // ConfiguraÃ§Ãµes do sistema
    'config.listar' => ['arquivo' => __DIR__ . '/../app/controladores/config.php', 'func' => 'listar'],
    'config.salvar' => ['arquivo' => __DIR__ . '/../app/controladores/config.php', 'func' => 'salvar'],
    'config.excluir' => ['arquivo' => __DIR__ . '/../app/controladores/config.php', 'func' => 'excluir'],

    // ConfiguraÃ§Ãµes de e-mail
    'config.email' => ['arquivo' => __DIR__ . '/../app/controladores/config.php', 'func' => 'email'],
    'config.email_salvar' => ['arquivo' => __DIR__ . '/../app/controladores/config.php', 'func' => 'email_salvar'],
    'config.email_teste' => ['arquivo' => __DIR__ . '/../app/controladores/config.php', 'func' => 'email_teste'],
    // Configurar interface
    'config.interface' => ['arquivo' => __DIR__ . '/../app/controladores/config.php', 'func' => 'interface_cfg'],
    'config.interface_salvar' => ['arquivo' => __DIR__ . '/../app/controladores/config.php', 'func' => 'interface_salvar'],

    // Tema (Sistema)
    'tema.listar' => ['arquivo' => __DIR__ . '/../app/controladores/tema.php', 'func' => 'listar'],
    'tema.salvar' => ['arquivo' => __DIR__ . '/../app/controladores/tema.php', 'func' => 'salvar'],
    'tema.aplicar' => ['arquivo' => __DIR__ . '/../app/controladores/tema.php', 'func' => 'aplicar'],
    'tema.excluir' => ['arquivo' => __DIR__ . '/../app/controladores/tema.php', 'func' => 'excluir'],
];

if (!isset($rotas[$acao])) {
    http_response_code(404);
    $mensagem = 'Rota não encontrada: ' . (string)$acao;
    include __DIR__ . '/../app/visoes/erros/nao_encontrado.php';
    exit;
}

// Controle de ACL: somente se logado e nÃ£o for rota pÃºblica nem o ping
if (usuario_logado()) {
    $acoes_livres = array_merge($acoes_publicas, [
        'dashboard.inicio', 'monitor.ping', 'dev.aplicar_sql', 'autenticacao.sair',
        // Rotas liberadas para qualquer usuÃ¡rio autenticado
        'conta.meu', 'conta.salvar',
        // NavegaÃ§Ã£o dinÃ¢mica de menu
        'menu.nav'
    ]);
    if (!in_array($acao, $acoes_livres, true)) {
        if (!\App\Lib\acl_permite_acao($acao)) {
            http_response_code(403);
            // PÃ¡gina amigÃ¡vel de acesso negado
            $acao_negada = $acao; // disponÃ­vel para a view
            include __DIR__ . '/../app/visoes/erros/acesso_negado.php';
            exit;
        }
    }
}

require_once $rotas[$acao]['arquivo'];
$func = $rotas[$acao]['func'];

// Registrar auditoria para aÃ§Ãµes com navegaÃ§Ã£o (nÃ£o ping, nÃ£o POST salvar/excluir)
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($acao !== 'monitor.ping' && !in_array($acao, $acoes_publicas, true)) {
    auditar_acao($acao, [
        'metodo' => $metodo,
        'query' => $_GET,
    ]);
}

// Executar a aÃ§Ã£o
call_user_func($func);





