<?php
declare(strict_types=1);

function inicio(): void {
    // Consome flash de sucesso (ex.: Bem-vindo) para não exibir tarja na tela inicial
    $flash = App\Lib\get_flash();
    if ($flash && (($flash['tipo'] ?? '') === 'success')) {
        $flash = null; // não mostra
    }
    include __DIR__ . '/../visoes/dashboard/inicio.php';
}
