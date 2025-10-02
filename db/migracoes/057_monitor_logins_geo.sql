SET search_path TO "adm", public;

DO $$
DECLARE v_menu_id BIGINT; v_sub_id BIGINT; v_item_id BIGINT; v_perfil_id BIGINT;
BEGIN
  -- Garante menu Administração
  SELECT id INTO v_menu_id FROM menu WHERE nome = 'Administração' LIMIT 1;
  IF v_menu_id IS NULL THEN
    INSERT INTO menu (nome, icone, ordem, ativo) VALUES ('Administração', 'fa-gear', 1, true) RETURNING id INTO v_menu_id;
  END IF;

  -- Submenu Monitoramento
  SELECT id INTO v_sub_id FROM submenu WHERE menu_id = v_menu_id AND nome = 'Monitoramento' LIMIT 1;
  IF v_sub_id IS NULL THEN
    INSERT INTO submenu (menu_id, nome, icone, ordem, ativo) VALUES (v_menu_id, 'Monitoramento', 'fa-signal', 5, true) RETURNING id INTO v_sub_id;
  END IF;

  -- Item Logins com Localização
  SELECT id INTO v_item_id FROM menu_item WHERE rota_acao = 'monitor.logins_geo' LIMIT 1;
  IF v_item_id IS NULL THEN
    INSERT INTO menu_item (submenu_id, nome, icone, rota_acao, ordem, ativo)
    VALUES (v_sub_id, 'Logins com Localização', 'fa-map-location-dot', 'monitor.logins_geo', 2, true)
    RETURNING id INTO v_item_id;
  END IF;

  -- Vincula ao perfil Administrador
  SELECT id INTO v_perfil_id FROM perfil WHERE nome = 'Administrador' LIMIT 1;
  IF v_perfil_id IS NOT NULL THEN
    INSERT INTO perfil_menu_item (perfil_id, menu_item_id)
    VALUES (v_perfil_id, v_item_id)
    ON CONFLICT DO NOTHING;
  END IF;
END $$;

