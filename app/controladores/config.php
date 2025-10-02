<?php

use function App\Lib\{set_flash, redirecionar};

require_once __DIR__ . '/../modelos/Config.php';

function listar(): void {
    $configs = App\Modelos\Config\listar();
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/config/listar.php';
}

// =============== Interface (UI) ===============
function interface_cfg(): void {
    $getP = fn(string $proc, string $k, ?string $def=null) => App\Modelos\Config\obter($proc, $k) ?? $def;
    $ui = [
        'NomeEmpresa' => $getP('Variavel de Ambiente','NomeEmpresa') ?? $getP('variavel_de_ambiente','NomeEmpresa') ?? 'Smarto',
        'tempoMaximoSessaoSegundos' => (string)($getP('Variavel de Ambiente','tempoMaximoSessaoSegundos') ?? $getP('sistema','tempoMaximoSessaoSegundos') ?? '0'),
        'sistema_em_manutencao' => (string)($getP('Variavel de Ambiente','sistema_em_manutencao') ?? $getP('sistema','sistema_em_manutencao') ?? 'false'),
        'sessao_mostrar_cronometro' => (string)($getP('Variavel de Ambiente','sessao_mostrar_cronometro') ?? $getP('sistema','sessao_mostrar_cronometro') ?? 'false'),
        'refresh_menu' => (string)($getP('menu','refresh_menu') ?? '30'),
        'tempo_atualiza_dash_monitoramento' => (string)($getP('monitoramento','tempo_atualiza_dash_monitoramento') ?? '45'),
        'janela_online_segundos' => (string)($getP('monitoramento','janela_online_segundos') ?? '120'),
    ];
    $flash = App\Lib\get_flash();
    // Acrescenta novas variÃ¡veis de ambiente Ã  UI
    $ui['urlSistema'] = (string)($getP('Variavel de Ambiente','urlSistema') ?? '');
    $ui['ForcaLocGeo'] = (string)($getP('Variavel de Ambiente','ForcaLocGeo') ?? 'false');
    $ui['forcaTrocaSenhaDias'] = (string)($getP('Variavel de Ambiente','forcaTrocaSenhaDias') ?? '0');
    $ui['aviso_troca_senha'] = (string)($getP('Variavel de Ambiente','aviso_troca_senha') ?? '0');
    $ui['senhaForte'] = (string)($getP('Variavel de Ambiente','senhaForte') ?? 'false');
    include __DIR__ . '/../visoes/config/interface.php';
}

function interface_salvar(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();
    $uid = App\Lib\usuario_logado()['id'] ?? null;

    $nomeEmpresa = trim((string)($_POST['NomeEmpresa'] ?? 'Smarto'));
    $ttl = trim((string)($_POST['tempoMaximoSessaoSegundos'] ?? '0'));
    $manut = isset($_POST['sistema_em_manutencao']) ? 'true' : 'false';
    $showCron = isset($_POST['sessao_mostrar_cronometro']) ? 'true' : 'false';
    $refreshMenu = trim((string)($_POST['refresh_menu'] ?? '30'));
    $dashRefresh = trim((string)($_POST['tempo_atualiza_dash_monitoramento'] ?? '45'));
    $janela = trim((string)($_POST['janela_online_segundos'] ?? '120'));

    $ttl = (string)max(0, (int)$ttl);
    $refreshMenu = (string)min(3600, max(5, (int)$refreshMenu));
    $dashRefresh = (string)max(5, (int)$dashRefresh);
    $janela = (string)max(15, (int)$janela);

    $upsert = function(string $proc, string $key, string $value, string $tipo='string') use ($uid){
        $st = App\DB\pdo()->prepare('SELECT id FROM config_sistema WHERE lower(processo)=lower(:p) AND lower(chave)=lower(:c)');
        $st->execute([':p'=>$proc, ':c'=>$key]);
        $id = $st->fetchColumn();
        App\Modelos\Config\salvar($id ? (int)$id : null, $proc, $key, $value, $tipo, null, $uid);
    };

    // VariÃ¡veis de Ambiente
    $upsert('Variavel de Ambiente','NomeEmpresa', $nomeEmpresa, 'string');
    $upsert('Variavel de Ambiente','tempoMaximoSessaoSegundos', $ttl, 'int');
    $upsert('Variavel de Ambiente','sistema_em_manutencao', $manut, 'bool');
    $upsert('Variavel de Ambiente','sessao_mostrar_cronometro', $showCron, 'bool');
    // Monitoramento
    $upsert('menu','refresh_menu', $refreshMenu, 'int');
    $upsert('monitoramento','tempo_atualiza_dash_monitoramento', $dashRefresh, 'int');
    $upsert('monitoramento','janela_online_segundos', $janela, 'int');

    // Novas chaves de Variáveis de Ambiente (UI)
    try {
        $upsert('Variavel de Ambiente','urlSistema', trim((string)($_POST['urlSistema'] ?? '')), 'string');
        $upsert('Variavel de Ambiente','ForcaLocGeo', isset($_POST['ForcaLocGeo']) ? 'true' : 'false', 'bool');
        $upsert('Variavel de Ambiente','forcaTrocaSenhaDias', (string)max(0, (int)($_POST['forcaTrocaSenhaDias'] ?? '0')), 'int');
        $upsert('Variavel de Ambiente','aviso_troca_senha', (string)max(0, (int)($_POST['aviso_troca_senha'] ?? '0')), 'int');
        $upsert('Variavel de Ambiente','senhaForte', isset($_POST['senhaForte']) ? 'true' : 'false', 'bool');
    } catch (\Throwable $e) { /* ignora */ }

    set_flash('success', 'Configurações de interface salvas.');
    redirecionar('config.interface');
}

// ================= Email =================
function email(): void {
    $get = fn(string $k, ?string $def=null) => App\Modelos\Config\obter('email', $k) ?? $def;
    $cfg = [
        'provider'    => (string)($get('provider','gmail')),
        'from_email'  => (string)($get('from_email','')),
        'from_name'   => (string)($get('from_name','')),
        'smtp_host'   => (string)($get('smtp_host','smtp.gmail.com')),
        'smtp_port'   => (string)($get('smtp_port','587')),
        'smtp_secure' => (string)($get('smtp_secure','tls')),
        'smtp_user'   => (string)($get('smtp_user','')),
        'smtp_pass'   => (string)($get('smtp_pass','')),
    ];
    $flash = App\Lib\get_flash();
    include __DIR__ . '/../visoes/config/email.php';
}

function email_salvar(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();
    $uid = App\Lib\usuario_logado()['id'] ?? null;

    $provider    = in_array(($_POST['provider'] ?? 'gmail'), ['gmail','custom'], true) ? (string)$_POST['provider'] : 'gmail';
    $from_email  = trim((string)($_POST['from_email'] ?? ''));
    $from_name   = trim((string)($_POST['from_name'] ?? ''));
    $smtp_host   = trim((string)($_POST['smtp_host'] ?? ''));
    $smtp_port   = (string)($_POST['smtp_port'] ?? '587');
    $smtp_secure = in_array(($_POST['smtp_secure'] ?? 'tls'), ['tls','ssl','none'], true) ? (string)$_POST['smtp_secure'] : 'tls';
    $smtp_user   = trim((string)($_POST['smtp_user'] ?? ''));
    $smtp_pass   = (string)($_POST['smtp_pass'] ?? '');

    $upsert = function(string $proc, string $key, string $value, string $tipo='string') use ($uid){
        $st = App\DB\pdo()->prepare('SELECT id FROM config_sistema WHERE lower(processo)=lower(:p) AND lower(chave)=lower(:c)');
        $st->execute([':p'=>$proc, ':c'=>$key]);
        $id = $st->fetchColumn();
        App\Modelos\Config\salvar($id ? (int)$id : null, $proc, $key, $value, $tipo, null, $uid);
    };

    $upsert('email','provider', $provider, 'string');
    $upsert('email','from_email', $from_email, 'string');
    $upsert('email','from_name', $from_name, 'string');
    $upsert('email','smtp_host', $smtp_host, 'string');
    $upsert('email','smtp_port', (string)max(1,(int)$smtp_port), 'int');
    $upsert('email','smtp_secure', $smtp_secure, 'string');
    $upsert('email','smtp_user', $smtp_user, 'string');
    if ($smtp_pass !== '') { $upsert('email','smtp_pass', $smtp_pass, 'string'); }

    set_flash('success', 'Configurações de e-mail salvas.');
    redirecionar('config.email');
}

function email_teste(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();
    $dest = trim((string)($_POST['destinatario'] ?? ''));
    if ($dest === '') { set_flash('danger', 'Informe o e-mail de destino.'); redirecionar('config.email'); }

    $get = fn(string $k, ?string $def=null) => App\Modelos\Config\obter('email', $k) ?? $def;
    $cfg = [
        'from_email'  => (string)($get('from_email','')),
        'from_name'   => (string)($get('from_name','')),
        'smtp_host'   => (string)($get('smtp_host','')),
        'smtp_port'   => (int)($get('smtp_port','587')),
        'smtp_secure' => (string)($get('smtp_secure','tls')),
        'smtp_user'   => (string)($get('smtp_user','')),
        'smtp_pass'   => (string)($get('smtp_pass','')),
    ];
    $assunto = 'Teste de e-mail - Smarto';
    $mensagem = "Teste de envio realizado em ".date('c').".\nSe você recebeu este e-mail, a configuração está funcionando.";

    $erro = null;
    $ok = \App\Lib\Email\enviar_email_smtp($cfg, $dest, $assunto, $mensagem, $erro);
    if ($ok) set_flash('success', 'E-mail de teste enviado para ' . htmlspecialchars($dest));
    else set_flash('danger', 'Falha ao enviar: ' . $erro);
    redirecionar('config.email');
}

function salvar(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $processo = trim((string)($_POST['processo'] ?? ''));
    $chave = trim((string)($_POST['chave'] ?? ''));
    $valor = (string)($_POST['valor'] ?? '');
    $tipo = trim((string)($_POST['tipo'] ?? 'string'));
    $descricao = trim((string)($_POST['descricao'] ?? ''));
    if ($processo === '' || $chave === '') {
        set_flash('danger', 'Informe processo e chave.');
        redirecionar('config.listar');
    }
    $uid = App\Lib\usuario_logado()['id'] ?? null;
    $novoId = App\Modelos\Config\salvar($id ?: null, $processo, $chave, $valor, $tipo, $descricao, $uid);
    set_flash('success', 'Configuração salva.');
    redirecionar('config.listar');
}

function excluir(): void {
    App\Lib\confirmar_post();
    App\Lib\CSRF\validar();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { set_flash('danger', 'ID inválido.'); redirecionar('config.listar'); }
    App\Modelos\Config\excluir($id);
    set_flash('success', 'Configuração excluída.');
    redirecionar('config.listar');
}

