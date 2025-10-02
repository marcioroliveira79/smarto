<?php
declare(strict_types=1);

namespace App\Lib\Email;

use function App\Modelos\Config\obter as cfg;

function enviar(string $to, string $subject, string $body, ?string &$erro = null): bool {
    $from_email  = cfg('email', 'from_email') ?? '';
    $from_name   = cfg('email', 'from_name') ?? '';
    $smtp_host   = cfg('email', 'smtp_host') ?? '';
    $smtp_port   = (int)(cfg('email', 'smtp_port') ?? 587);
    $smtp_secure = cfg('email', 'smtp_secure') ?? 'tls';
    $smtp_user   = cfg('email', 'smtp_user') ?? '';
    $smtp_pass   = cfg('email', 'smtp_pass') ?? '';
    if ($from_name === '') {
        $n = cfg('Variavel de Ambiente', 'NomeEmpresa') ?? cfg('variavel_de_ambiente', 'NomeEmpresa') ?? '';
        if (is_string($n) && trim($n) !== '') { $from_name = trim($n); }
        else { $from_name = 'Smarto'; }
    }
    $cfg = compact('from_email','from_name','smtp_host','smtp_port','smtp_secure','smtp_user','smtp_pass');
    return enviar_email_smtp($cfg, $to, $subject, $body, $erro);
}

function enviar_email_smtp(array $cfg, string $to, string $subject, string $body, ?string &$erro = null): bool {
    $host = (string)($cfg['smtp_host'] ?? '');
    $port = (int)($cfg['smtp_port'] ?? 587);
    $secure = (string)($cfg['smtp_secure'] ?? 'tls'); // tls|ssl|none
    $user = (string)($cfg['smtp_user'] ?? '');
    $pass = (string)($cfg['smtp_pass'] ?? '');
    $from = (string)($cfg['from_email'] ?? $user);
    $fromName = (string)($cfg['from_name'] ?? 'Smarto');
    $timeout = 15;

    $transport = ($secure === 'ssl') ? 'ssl://' : '';
    $remote = $transport . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { $erro = "Conexão falhou: $errstr ($errno)"; return false; }
    $read = function() use ($fp){ return rtrim((string)stream_get_line($fp, 8192, "\n")); };
    $write = function(string $cmd) use ($fp){ fwrite($fp, $cmd."\r\n"); };
    $drainMulti = function(string $expectedCode) use ($read): bool {
        $line = $read(); if ($line === '' || $line === false) return false;
        $last = $line;
        while (strlen((string)$line) >= 4 && $line[3] === '-') { $line = $read(); if ($line === '' || $line === false) break; $last = $line; }
        return is_string($last) && str_starts_with($last, $expectedCode);
    };

    $banner = $read(); if (!$banner || !str_starts_with($banner, '220')) { $erro = 'Sem banner SMTP'; fclose($fp); return false; }
    $write('EHLO smarto.local'); if (!$drainMulti('250')) { $erro = 'EHLO falhou'; fclose($fp); return false; }
    if ($secure === 'tls') {
        $write('STARTTLS'); if (!$drainMulti('220')) { $erro = 'STARTTLS não suportado'; fclose($fp); return false; }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $erro = 'TLS handshake falhou'; fclose($fp); return false; }
        $write('EHLO smarto.local'); if (!$drainMulti('250')) { $erro = 'EHLO pós-TLS falhou'; fclose($fp); return false; }
    }
    $write('AUTH LOGIN'); if (!$drainMulti('334')) { $erro = 'AUTH LOGIN não aceito'; fclose($fp); return false; }
    $write(base64_encode($user)); if (!$drainMulti('334')) { $erro = 'Usuário rejeitado'; fclose($fp); return false; }
    $write(base64_encode($pass)); if (!$drainMulti('235')) { $erro = 'Senha rejeitada'; fclose($fp); return false; }
    $write('MAIL FROM: <'.$from.'>'); if (!$drainMulti('250')) { $erro = 'MAIL FROM falhou'; fclose($fp); return false; }
    $write('RCPT TO: <'.$to.'>'); if (!$drainMulti('250') && !$drainMulti('251')) { $erro = 'RCPT TO falhou'; fclose($fp); return false; }
    $write('DATA'); if (!$drainMulti('354')) { $erro = 'DATA não aceito'; fclose($fp); return false; }
    $headers = [
        'From: '.sprintf('"%s" <%s>', addcslashes($fromName, '"'), $from),
        'To: <'.$to.'>',
        'Subject: '.mb_encode_mimeheader($subject, 'UTF-8'),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $write(implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.");
    if (!$drainMulti('250')) { $erro = 'Envio rejeitado'; fclose($fp); return false; }
    $write('QUIT');
    fclose($fp);
    return true;
}

