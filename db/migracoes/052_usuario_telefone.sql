SET search_path TO "adm", public;

-- Adiciona telefone ao usuário (obrigatório e único)
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS telefone VARCHAR(20);

-- Preenche telefones fake para registros existentes (formato BR)
UPDATE usuario u
SET telefone = COALESCE(
  telefone,
  '('
  || LPAD(((10 + (u.id % 89))::text), 2, '0') || ') '
  || CASE WHEN (u.id % 2)=0 THEN '9' ELSE '' END
  || LPAD(((1000 + (u.id * 3))::text), CASE WHEN (u.id % 2)=0 THEN 4 ELSE 4 END, '0')
  || '-' || LPAD(((1000 + (u.id * 7))::text), 4, '0')
);

ALTER TABLE usuario ALTER COLUMN telefone SET NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuario_telefone ON usuario(telefone);

