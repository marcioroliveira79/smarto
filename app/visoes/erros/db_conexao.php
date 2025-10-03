<?php
// Tela amigável para erro de conexão com o banco de dados
?><!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Erro de Conexão</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f8fafc; }
    .error-card { max-width: 420px; margin: 80px auto; }
    .error-icon { font-size: 3rem; color: #dc3545; }
  </style>
</head>
<body>
  <div class="card shadow error-card">
    <div class="card-body text-center">
      <div class="error-icon mb-3"><i class="fa fa-database"></i></div>
      <h2 class="mb-2 text-danger">Não foi possível conectar ao banco de dados</h2>
      <p class="mb-3">Verifique as configurações de acesso, usuário, senha e disponibilidade do servidor.<br>Se o problema persistir, contate o suporte técnico.</p>
      <a href="/smarto/publico" class="btn app-action w-100" style="background-color: var(--app-action, #212529); border-color: var(--app-action, #212529); color: #fff;"><i class="fa fa-arrow-rotate-left me-1"></i>Tentar novamente</a>
    </div>
  </div>
</body>
</html>
