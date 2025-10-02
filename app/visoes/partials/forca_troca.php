<?php
// Enforce: while password not changed (or expired), allow only change password or logout
try {
    if (!isset($usuario_sessao) && function_exists('App\\Lib\\usuario_logado')) {
        $usuario_sessao = App\Lib\usuario_logado();
    }
    if ($usuario_sessao && isset($usuario_sessao['id'])) {
        $uDados = \App\Modelos\Usuario\buscar((int)$usuario_sessao['id']) ?: [];
        $trocaEm = $uDados['senha_trocada_em'] ?? null;
        $forceDays = 0;
        try {
            $v = \App\Modelos\Config\obter('Variavel de Ambiente', 'forcaTrocaSenhaDias');
            if ($v === null) { $v = \App\Modelos\Config\obter('Variavel de Ambiente', 'forcaTrocaSenhaDias'); }
            
            $forceDays = (int)trim((string)($v ?? '0'));
        } catch (\Throwable $e) { $forceDays = 0; }

        $needForce = false;
        if (empty($trocaEm)) { $needForce = true; }
        else if ($forceDays > 0) {
            $deadline = strtotime((string)$trocaEm) + ($forceDays * 86400);
            if (time() >= $deadline) { $needForce = true; }
        }

        if ($needForce) {
            $acao = (string)($_GET['acao'] ?? '');
            // Allow only password change and logout
            if ($acao !== 'conta.meu' && $acao !== 'conta.salvar' && $acao !== 'autenticacao.sair') {
                \App\Lib\set_flash('warning', 'Troque sua senha para continuar.');
                \App\Lib\redirecionar('conta.meu');
            }
        }
    }
} catch (\Throwable $e) { /* noop */ }
?>
