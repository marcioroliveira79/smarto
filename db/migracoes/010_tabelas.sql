SET search_path TO "adm", public;

-- Tabelas principais
CREATE TABLE IF NOT EXISTS perfil (
  id          BIGSERIAL PRIMARY KEY,
  nome        VARCHAR(100) NOT NULL UNIQUE,
  descricao   VARCHAR(255),
  ativo       BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS usuario (
  id           BIGSERIAL PRIMARY KEY,
  nome         VARCHAR(150) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  senha_hash   VARCHAR(255) NOT NULL,
  ativo        BOOLEAN NOT NULL DEFAULT TRUE,
  perfil_id    BIGINT NOT NULL REFERENCES perfil(id),
  criado_em    TIMESTAMPTZ NOT NULL DEFAULT now(),
  atualizado_em TIMESTAMPTZ
);

-- Tabelas de clínica removidas deste MVP

CREATE TABLE IF NOT EXISTS menu (
  id     BIGSERIAL PRIMARY KEY,
  nome   VARCHAR(100) NOT NULL,
  icone  VARCHAR(50),
  ordem  INT NOT NULL DEFAULT 0,
  ativo  BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS submenu (
  id       BIGSERIAL PRIMARY KEY,
  menu_id  BIGINT NOT NULL REFERENCES menu(id) ON DELETE CASCADE,
  nome     VARCHAR(100) NOT NULL,
  icone    VARCHAR(50),
  ordem    INT NOT NULL DEFAULT 0,
  ativo    BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS menu_item (
  id         BIGSERIAL PRIMARY KEY,
  menu_id    BIGINT REFERENCES menu(id) ON DELETE CASCADE,
  submenu_id BIGINT REFERENCES submenu(id) ON DELETE CASCADE,
  nome       VARCHAR(100) NOT NULL,
  icone      VARCHAR(50),
  rota_acao  VARCHAR(100) NOT NULL,
  ordem      INT NOT NULL DEFAULT 0,
  ativo      BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT menu_or_submenu CHECK ((submenu_id IS NOT NULL) OR (menu_id IS NOT NULL))
);

CREATE TABLE IF NOT EXISTS perfil_menu_item (
  perfil_id     BIGINT NOT NULL REFERENCES perfil(id) ON DELETE CASCADE,
  menu_item_id  BIGINT NOT NULL REFERENCES menu_item(id) ON DELETE CASCADE,
  PRIMARY KEY (perfil_id, menu_item_id)
);

CREATE TABLE IF NOT EXISTS log_auditoria (
  id        BIGSERIAL PRIMARY KEY,
  usuario_id BIGINT REFERENCES usuario(id),
  acao      VARCHAR(100) NOT NULL,
  detalhes  JSONB,
  ip        VARCHAR(45),
  user_agent VARCHAR(255),
  criado_em TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS login_tentativa (
  id        BIGSERIAL PRIMARY KEY,
  email     VARCHAR(150) NOT NULL,
  ip        VARCHAR(45),
  sucesso   BOOLEAN NOT NULL,
  criado_em TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS usuario_online (
  id          BIGSERIAL PRIMARY KEY,
  usuario_id  BIGINT NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
  ip          VARCHAR(45),
  user_agent  VARCHAR(255),
  ultimo_sinal TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE (usuario_id)
);
