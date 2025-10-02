<?php
declare(strict_types=1);

namespace App\Lib\Tema;

require_once __DIR__ . '/../modelos/Config.php';
require_once __DIR__ . '/../modelos/Tema.php';

function cores(): array {
    return [
        'primaria' => '#0d6efd',
        'secundaria' => '#6c757d',
        'sucesso' => '#198754',
        'perigo' => '#dc3545',
        'aviso' => '#ffc107',
        'info' => '#0dcaf0',
        'claro' => '#f8f9fa',
        'escuro' => '#212529',
    ];
}

function btn_classes(string $tipo): string {
    $map = [
        'voltar' => 'btn btn-outline-secondary',
        'salvar' => 'btn btn-success',
        'excluir' => 'btn btn-danger',
        'itens' => 'btn btn-primary',
        'sair' => 'btn btn-outline-danger',
        'editar' => 'btn btn-primary',
        'aplicar' => 'btn btn-warning',
        'padrao' => 'btn btn-primary',
    ];
    return $map[$tipo] ?? $map['padrao'];
}

function icone(string $tipo): string {
    $map = [
        'voltar' => 'fa-arrow-left',
        'salvar' => 'fa-floppy-disk',
        'excluir' => 'fa-trash',
        'itens' => 'fa-list',
        'sair' => 'fa-right-from-bracket',
        'editar' => 'fa-pen-to-square',
        'aplicar' => 'fa-check-double',
        'usuario' => 'fa-user',
        'perfil' => 'fa-id-badge',
        'menu' => 'fa-bars',
        'home' => 'fa-house',
    ];
    return $map[$tipo] ?? 'fa-circle';
}

function spinner(string $texto = 'Processando...'): string {
    return '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function hex_to_rgb(string $hex): array {
    $h = ltrim($hex, '#');
    if (strlen($h) === 3) { $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2]; }
    $int = hexdec($h);
    return [($int >> 16) & 255, ($int >> 8) & 255, $int & 255];
}

function clamp(int $v, int $min=0, int $max=255): int { return max($min, min($max, $v)); }

function adjust_brightness(string $hex, int $delta): string {
    [$r,$g,$b] = hex_to_rgb($hex);
    $r = clamp($r + $delta); $g = clamp($g + $delta); $b = clamp($b + $delta);
    return sprintf('#%02X%02X%02X', $r, $g, $b);
}

function render_css_vars(): void {
    try {
        $ativoId = \App\Modelos\Config\obter('Variavel de Ambiente','tema_ativo_id');
        $tema = null;
        if ($ativoId !== null && trim((string)$ativoId) !== '') {
            $tema = \App\Modelos\Tema\buscar((int)$ativoId);
        }
        $cores = [
            'nav' => '#0d6efd',
            'action' => '#0d6efd',
            'text_accent' => '#0d6efd',
            'back' => '#6c757d',
            'delete' => '#dc3545',
        ];
        if ($tema && !empty($tema['cores'])) {
            $json = is_array($tema['cores']) ? $tema['cores'] : json_decode((string)$tema['cores'], true);
            if (is_array($json)) {
                $cores = array_merge($cores, array_intersect_key($json, $cores));
            }
        }
        $nav = $cores['nav'];
        $navHover = adjust_brightness($nav, -10);
        $navActive= adjust_brightness($nav, -20);
        $act = $cores['action'];
        $actHover = adjust_brightness($act, -10);
        $actActive= adjust_brightness($act, -20);
        $back = $cores['back'];
        $backHover = adjust_brightness($back, -10);
        $del = $cores['delete'];
        $delHover = adjust_brightness($del, -10);
        $txt = $cores['text_accent'];

        // Rings (glow) in RGBA for focus/hover
        [$ar,$ag,$ab] = hex_to_rgb($act);
        [$br,$bg,$bb] = hex_to_rgb($back);
        [$dr,$dg,$db] = hex_to_rgb($del);
        $ringAction = "rgba($ar,$ag,$ab,0.35)";
        $ringBack   = "rgba($br,$bg,$bb,0.35)";
        $ringDel    = "rgba($dr,$dg,$db,0.35)";

        echo "\n<style>\n:root{--color-nav:$nav;--color-nav-hover:$navHover;--color-nav-active:$navActive;--color-action:$act;--color-action-hover:$actHover;--color-action-active:$actActive;--color-back:$back;--color-back-hover:$backHover;--color-delete:$del;--color-delete-hover:$delHover;--color-text-accent:$txt;--ring-action:$ringAction;--ring-back:$ringBack;--ring-delete:$ringDel;}\n";
        // Navbar e cabeçalhos primários
        echo ".navbar.bg-primary,.navbar-dark.bg-primary{background-color:var(--color-nav)!important;border-color:var(--color-nav)!important;}\n";
        echo ".navbar .navbar-brand, .navbar .nav-link, .navbar .navbar-text{color:#fff !important;}\n";
        echo ".card .card-header.bg-primary{background-color:var(--color-nav)!important;border-color:var(--color-nav)!important;color:#fff;}\n";
        // Botões de ação unificados
        echo ".btn-primary,.btn-success,.btn-info,.btn-warning{background-color:var(--color-action)!important;border-color:var(--color-action)!important;color:#fff!important;}\n";
        echo ".btn-primary:hover,.btn-success:hover,.btn-info:hover,.btn-warning:hover{background-color:var(--color-action-hover)!important;border-color:var(--color-action-hover)!important;box-shadow:0 0 0 .25rem var(--ring-action)!important;}\n";
        echo ".btn-primary:focus,.btn-primary:focus-visible,.btn-success:focus,.btn-success:focus-visible,.btn-info:focus,.btn-info:focus-visible,.btn-warning:focus,.btn-warning:focus-visible{box-shadow:0 0 0 .28rem var(--ring-action)!important;}\n";
        echo ".btn-primary:active,.btn-success:active,.btn-info:active,.btn-warning:active{background-color:var(--color-action-active)!important;border-color:var(--color-action-active)!important;box-shadow:inset 0 2px 6px rgba(0,0,0,.18),0 0 0 .25rem var(--ring-action)!important;transform:translateY(1px) scale(0.985);}\n";
        // Botão Voltar (usa cor definida também em estado normal)
        echo ".btn-outline-secondary{background-color:var(--color-back)!important;border-color:var(--color-back)!important;color:#fff!important;}\n";
        echo ".btn-outline-secondary:hover{background-color:var(--color-back-hover)!important;border-color:var(--color-back-hover)!important;color:#fff!important;box-shadow:0 0 0 .25rem var(--ring-back)!important;}\n";
        echo ".btn-outline-secondary:focus,.btn-outline-secondary:focus-visible{box-shadow:0 0 0 .28rem var(--ring-back)!important;}\n";
        echo ".btn-outline-secondary:active{background-color:var(--color-back-hover)!important;border-color:var(--color-back-hover)!important;color:#fff!important;box-shadow:inset 0 2px 6px rgba(0,0,0,.18),0 0 0 .25rem var(--ring-back)!important;transform:translateY(1px) scale(0.985);}\n";
        // Botão Excluir
        echo ".btn-danger{background-color:var(--color-delete)!important;border-color:var(--color-delete)!important;}\n";
        echo ".btn-danger:hover{background-color:var(--color-delete-hover)!important;border-color:var(--color-delete-hover)!important;box-shadow:0 0 0 .25rem var(--ring-delete)!important;}\n";
        echo ".btn-danger:focus,.btn-danger:focus-visible{box-shadow:0 0 0 .28rem var(--ring-delete)!important;}\n";
        echo ".btn-danger:active{background-color:var(--color-delete-hover)!important;border-color:var(--color-delete-hover)!important;box-shadow:inset 0 2px 6px rgba(0,0,0,.18),0 0 0 .25rem var(--ring-delete)!important;transform:translateY(1px) scale(0.985);}\n";
        // Paginador
        echo ".pagination .page-link{color:var(--color-action);} .pagination .page-link:hover{background-color:rgba(0,0,0,.03);} .pagination .active .page-link{background-color:var(--color-action)!important;border-color:var(--color-action)!important;color:#fff;}\n";
        // Acentos de texto e ícones
        echo ".app-accent,.app-accent i{color:var(--color-text-accent)!important;}\n";
        echo "</style>\n";
    } catch (\Throwable $e) { /* silencioso */ }
}
