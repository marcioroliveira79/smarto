-- Criação da tabela de localização de login
CREATE TABLE IF NOT EXISTS login_localizacao (
  id bigserial PRIMARY KEY,
  usuario_id integer NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
  sessao_id text NULL,
  login_em timestamptz NOT NULL DEFAULT now(),
  fonte text NOT NULL CHECK (fonte IN ('device','ip')),
  permissao text NOT NULL CHECK (permissao IN ('granted','denied','unavailable')),
  latitude numeric(9,6) NULL,
  longitude numeric(9,6) NULL,
  precisao_m numeric(10,2) NULL,
  ip inet NULL,
  user_agent text NULL,
  capturado_em timestamptz NOT NULL DEFAULT now(),
  observacoes text NULL
);

CREATE INDEX IF NOT EXISTS idx_login_localizacao_usuario_em
  ON login_localizacao (usuario_id, login_em DESC);

CREATE INDEX IF NOT EXISTS idx_login_localizacao_em
  ON login_localizacao (login_em DESC);

