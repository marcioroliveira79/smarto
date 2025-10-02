SET search_path TO "adm", public;

-- Tabela para tokens de recuperação de senha
CREATE TABLE IF NOT EXISTS senha_reset (
  id          BIGSERIAL PRIMARY KEY,
  usuario_id  BIGINT NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
  token       VARCHAR(100) NOT NULL UNIQUE,
  expira_em   TIMESTAMPTZ NOT NULL,
  usado_em    TIMESTAMPTZ,
  criado_em   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_senha_reset_usuario ON senha_reset(usuario_id);
CREATE INDEX IF NOT EXISTS idx_senha_reset_expira  ON senha_reset(expira_em);

