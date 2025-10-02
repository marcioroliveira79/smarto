SET search_path TO "adm", public;

-- Acrescenta autor e datas em config_sistema
ALTER TABLE config_sistema ADD COLUMN IF NOT EXISTS criado_em TIMESTAMPTZ NOT NULL DEFAULT now();
ALTER TABLE config_sistema ADD COLUMN IF NOT EXISTS criado_por BIGINT;
ALTER TABLE config_sistema ADD COLUMN IF NOT EXISTS atualizado_por BIGINT;

-- Preenche registros existentes com usuário administrador (id=1)
UPDATE config_sistema SET criado_por = COALESCE(criado_por, 1), atualizado_por = COALESCE(atualizado_por, 1);

-- Chaves estrangeiras (não estritas; permitem NULL)
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints WHERE table_schema='adm' AND table_name='config_sistema' AND constraint_name='fk_config_criado_por'
  ) THEN
    ALTER TABLE config_sistema ADD CONSTRAINT fk_config_criado_por FOREIGN KEY (criado_por) REFERENCES usuario(id) ON DELETE SET NULL;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints WHERE table_schema='adm' AND table_name='config_sistema' AND constraint_name='fk_config_atualizado_por'
  ) THEN
    ALTER TABLE config_sistema ADD CONSTRAINT fk_config_atualizado_por FOREIGN KEY (atualizado_por) REFERENCES usuario(id) ON DELETE SET NULL;
  END IF;
END $$;

