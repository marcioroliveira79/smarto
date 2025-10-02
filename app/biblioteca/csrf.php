<?php
declare(strict_types=1);

namespace App\Lib\CSRF;

function token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function input(): string {
    $t = token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
}

function validar(): void {
    $post = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $post)) {
        http_response_code(400);
        echo 'CSRF inválido';
        exit;
    }
}

