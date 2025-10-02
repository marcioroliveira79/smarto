# ClÃ­nica (MVP) â€“ PHP puro + PostgreSQL

Projeto minimalista sem framework, com RBAC por perfis, seleÃ§Ã£o de clÃ­nica no login e menus carregados do banco. Front via CDN: Bootstrap 5, Font Awesome 6, jQuery e DataTables.

## Requisitos
- PHP 8.1+
- PostgreSQL 13+

## ConfiguraÃ§Ã£o
1) Banco de dados sugerido:
   - Banco: `smarto`
   - Schema: `adm`
   - UsuÃ¡rio: `smarto_adm`
   - Senha: `123456`

   Exemplo no psql (opcional):
   ```sql
   CREATE DATABASE smarto;
   CREATE USER smarto_adm WITH PASSWORD '123456';
   GRANT ALL PRIVILEGES ON DATABASE smarto TO smarto_adm;
   ```

2) Ajuste `.env` (raiz) se necessÃ¡rio:
```
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=smarto
DB_USER=smarto_adm
DB_SENHA=123456
DB_SCHEMA=adm
BASE_PATH=
APP_TIMEZONE=America/Sao_Paulo
```

3) Rodar migraÃ§Ãµes e seed:
OpÃ§Ã£o A â€“ CLI (recomendado):
```
php scripts/run_sql.php
```

OpÃ§Ã£o B â€“ Navegador (somente APP_ENV=local):
```
http://localhost/smarto/index.php?acao=dev.aplicar_sql
```

Obs.: O seed cria o usuÃ¡rio `admin@clinica.local` com senha `password` e associa a uma clÃ­nica padrÃ£o. TambÃ©m cria o menu â€œAdministraÃ§Ã£o > Cadastros > UsuÃ¡rios/Perfisâ€ vinculado ao perfil Administrador.

## Executar no servidor HTTP
- Laragon/Apache (pasta em `c:\laragon\www\smarto`):
  - Acesse: `http://localhost/smarto/` (funciona a partir da raiz)
  - O arquivo `index.php` na raiz encaminha para o roteador em `publico/index.php`.

## Estrutura
- `publico/index.php` â€“ roteador simples por `acao`
  - Um atalho `index.php` na raiz redireciona para ele, permitindo `http://localhost/smarto/`.
- `app/config/*` â€“ env, config, conexÃ£o PDO
- `app/biblioteca/*` â€“ util, csrf, autenticacao, acl, tema
- `app/controladores/*` â€“ controladores
- `app/modelos/*` â€“ acesso ao banco
- `app/visoes/**` â€“ cabeÃ§alho, rodapÃ© e pÃ¡ginas
- `db/migracoes/*.sql` â€“ schema e tabelas
- `db/semente/000_seed.sql` â€“ dados iniciais
- `scripts/run_sql.php` â€“ aplica SQLs via PDO do app

## Notas
- Rate-limit de login: 5 falhas por IP+email em 15 minutos.
- Auditoria registra login e acessos (IP e user-agent).
- PresenÃ§a: ping periÃ³dico em `monitor.ping` atualiza `usuario_online`.
- CSRF: todos os formulÃ¡rios tÃªm `csrf_token`.
- BotÃµes com Ã­cones e feedback visual; overlay â€œCarregando...â€ ao acionar.

## Padrões de arquivos (evitar faixa branca no topo)
- Codificação: UTF-8 sem BOM (todas as views/partials PHP).
- Sem conteúdo antes do HTML/<?php no início de arquivos PHP.
- Finais de linha: LF. Remover espaços em branco à direita.

CI/Local
- Verificação: php scripts/check_bom_whitespace.php (retorna erro se encontrar BOM).
- Git hook opcional: habilite com git config core.hooksPath .githooks para bloquear commits com BOM.
