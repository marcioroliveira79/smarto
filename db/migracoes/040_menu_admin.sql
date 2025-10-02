SET search_path TO "adm", public;

-- Garante item de menu para gerenciar Menus e Itens sob Administração > Cadastros
DO $$
DECLARE v_menu_id BIGINT; v_sub_id BIGINT; v_item_id BIGINT; v_perfil_id BIGINT;
BEGIN
  -- Menu Administração
  SELECT id INTO v_menu_id FROM menu WHERE nome = 'Administração' LIMIT 1;
  IF v_menu_id IS NULL THEN
    INSERT INTO menu (nome, icone, ordem, ativo) VALUES ('Administração', 'fa-gear', 1, true) RETURNING id INTO v_menu_id;
  END IF;

  -- Submenu Cadastros
  SELECT id INTO v_sub_id FROM submenu WHERE menu_id = v_menu_id AND nome = 'Cadastros' LIMIT 1;
  IF v_sub_id IS NULL THEN
    INSERT INTO submenu (menu_id, nome, icone, ordem, ativo) VALUES (v_menu_id, 'Cadastros', 'fa-folder-open', 1, true) RETURNING id INTO v_sub_id;
  END IF;

  -- Item Menus e Itens
  SELECT id INTO v_item_id FROM menu_item WHERE rota_acao = 'menu.listar' LIMIT 1;
  IF v_item_id IS NULL THEN
    INSERT INTO menu_item (submenu_id, nome, icone, rota_acao, ordem, ativo)
    VALUES (v_sub_id, 'Menus e Itens', 'fa-sitemap', 'menu.listar', 90, true)
    RETURNING id INTO v_item_id;
  END IF;

  -- Vincula ao perfil Administrador, se existir
  SELECT id INTO v_perfil_id FROM perfil WHERE nome = 'Administrador' LIMIT 1;
  IF v_perfil_id IS NOT NULL THEN
    INSERT INTO perfil_menu_item (perfil_id, menu_item_id)
    VALUES (v_perfil_id, v_item_id)
    ON CONFLICT DO NOTHING;
  END IF;
END $$;

