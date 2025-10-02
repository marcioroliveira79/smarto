SET search_path TO "adm", public;

-- Tabela de configurações dinâmicas de sistema
CREATE TABLE IF NOT EXISTS config_sistema (
  id           BIGSERIAL PRIMARY KEY,
  processo     VARCHAR(100) NOT NULL,
  chave        VARCHAR(120) NOT NULL,
  valor        TEXT,
  descricao    VARCHAR(255),
  tipo         VARCHAR(30) DEFAULT 'string', -- string, int, bool, json, seconds, etc
  atualizado_em TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE (processo, chave)
);

-- Item de menu: Administração > Cadastros > Configurações
DO $$
DECLARE v_menu_id BIGINT; v_sub_id BIGINT; v_item_id BIGINT; v_perfil_id BIGINT;
BEGIN
  SELECT id INTO v_menu_id FROM menu WHERE nome = 'Administração' LIMIT 1;
  IF v_menu_id IS NULL THEN
    INSERT INTO menu (nome, icone, ordem, ativo) VALUES ('Administração', 'fa-gear', 1, true) RETURNING id INTO v_menu_id;
  END IF;

  SELECT id INTO v_sub_id FROM submenu WHERE menu_id = v_menu_id AND nome = 'Cadastros' LIMIT 1;
  IF v_sub_id IS NULL THEN
    INSERT INTO submenu (menu_id, nome, icone, ordem, ativo) VALUES (v_menu_id, 'Cadastros', 'fa-folder-open', 1, true) RETURNING id INTO v_sub_id;
  END IF;

  SELECT id INTO v_item_id FROM menu_item WHERE rota_acao = 'config.listar' LIMIT 1;
  IF v_item_id IS NULL THEN
    INSERT INTO menu_item (submenu_id, nome, icone, rota_acao, ordem, ativo)
    VALUES (v_sub_id, 'Configurações', 'fa-sliders', 'config.listar', 15, true)
    RETURNING id INTO v_item_id;
  END IF;

  SELECT id INTO v_perfil_id FROM perfil WHERE nome = 'Administrador' LIMIT 1;
  IF v_perfil_id IS NOT NULL THEN
    INSERT INTO perfil_menu_item (perfil_id, menu_item_id)
    VALUES (v_perfil_id, v_item_id)
    ON CONFLICT DO NOTHING;
  END IF;
END $$;

