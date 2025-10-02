# Smarto System – Clínica (MVP)

Projeto minimalista em **PHP puro** com **PostgreSQL**, sem frameworks, focado em gestão clínica, RBAC por perfis, seleção de clínica no login e menus dinâmicos carregados do banco. Front-end via CDN: Bootstrap 5, Font Awesome 6, jQuery e DataTables.

---

## 🚀 Principais Recursos
- Autenticação segura e controle de acesso por perfis (RBAC)
- Seleção de clínica no login
- Menus dinâmicos vinculados ao perfil
- Auditoria de logins e acessos (IP, user-agent)
- Rate-limit de login (5 tentativas/IP+email/15min)
- Presença de usuários online (ping periódico)
- CSRF em todos os formulários
- Feedback visual: botões com ícones, overlay "Carregando..."

---

## 🛠 Requisitos
- **PHP 8.1+**
- **PostgreSQL 13+**

---

## ⚡ Instalação Rápida
1. **Banco de dados sugerido:**
   - Banco: `smarto`
   - Schema: `adm`
   - Usuário: `smarto_adm`
   - Senha: `123456`

   ```sql
   CREATE DATABASE smarto;
   CREATE USER smarto_adm WITH PASSWORD '123456';
   GRANT ALL PRIVILEGES ON DATABASE smarto TO smarto_adm;
   ```

2. **Configure o arquivo `.env` na raiz:**
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_NAME=smarto
   DB_USER=smarto_adm
   DB_SENHA=123456
   DB_SCHEMA=adm
   BASE_PATH=
   APP_TIMEZONE=America/Sao_Paulo
   ```

3. **Rode migrações e seed:**
   - CLI (recomendado):
     ```bash
     php scripts/run_sql.php
     ```
   - Navegador (somente APP_ENV=local):
     ```
     http://localhost/smarto/index.php?acao=dev.aplicar_sql
     ```

   > O seed cria o usuário `admin@clinica.local` (senha `password`) e associa à clínica padrão. Também cria o menu "Administração > Cadastros > Usuários/Perfis" vinculado ao perfil Administrador.

---

## 🌐 Executando o Projeto
- **Servidor HTTP (Laragon/Apache):**
  - Pasta: `c:\laragon\www\smarto`
  - URL: [http://localhost/smarto/](http://localhost/smarto/)
  - O arquivo `index.php` na raiz encaminha para o roteador em `publico/index.php`.

---

## 📁 Estrutura de Pastas
```
publico/index.php      # Roteador principal por 'acao'
index.php              # Atalho na raiz para o roteador
app/config/            # .env, config, conexão PDO
app/biblioteca/        # util, csrf, autenticacao, acl, tema
app/controladores/     # controladores
app/modelos/           # acesso ao banco
app/visoes/            # cabeçalho, rodapé, páginas
scripts/run_sql.php    # aplica SQLs via PDO do app
db/migracoes/          # schema e tabelas
 db/semente/           # dados iniciais
```

---

## 🧑‍💻 Padrões e Boas Práticas
- **Codificação:** UTF-8 sem BOM (todas as views/partials PHP)
- **Sem conteúdo antes do HTML/<?php** no início dos arquivos PHP
- **Finais de linha:** LF
- **Remover espaços em branco à direita**
- **Verificação:**
  - `php scripts/check_bom_whitespace.php` (retorna erro se encontrar BOM)
  - Git hook opcional: `git config core.hooksPath .githooks` para bloquear commits com BOM

---

## 🔒 Segurança
- Rate-limit de login
- Auditoria de logins/acessos
- CSRF em todos os formulários
- Senhas criptografadas (bcrypt)

---

## 📋 Observações
- Projeto MVP, fácil de estender e adaptar
- Sem dependências externas além das CDNs
- Estrutura modular e clara

---

## 📞 Suporte & Contribuição
Para dúvidas, sugestões ou contribuições, abra uma issue ou envie um pull request.
