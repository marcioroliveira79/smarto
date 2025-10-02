SET search_path TO "adm", public;

-- Perfis básicos
INSERT INTO perfil (nome, descricao, ativo) VALUES
  ('Administrador', 'Acesso total', true),
  ('Gerente', 'Acesso gerencial', true),
  ('Recepcionista', 'Acesso à recepção', true)
ON CONFLICT (nome) DO NOTHING;

-- Clínica padrão
INSERT INTO clinica (nome, ativo) VALUES ('Clínica Central', true)
ON CONFLICT DO NOTHING;

-- Usuário admin (senha: password)
DO $$
DECLARE adm_id BIGINT; per_id BIGINT; cli_id BIGINT; vhash TEXT;
BEGIN
  SELECT id INTO per_id FROM perfil WHERE nome='Administrador';
  SELECT id INTO cli_id FROM clinica WHERE nome='Clínica Central';
  SELECT crypt('password', gen_salt('bf')) INTO vhash;
  INSERT INTO usuario (nome, email, senha_hash, ativo, perfil_id)
  VALUES ('Administrador', 'admin@clinica.local', vhash, true, per_id)
  ON CONFLICT (email) DO NOTHING;
  SELECT id INTO adm_id FROM usuario WHERE email='admin@clinica.local';
  -- Associação do admin à clínica
  INSERT INTO usuario_clinica (usuario_id, clinica_id)
  VALUES (adm_id, cli_id) ON CONFLICT DO NOTHING;
END $$;

-- Menu e itens de exemplo (Usuários/Perfis)
DO $$
DECLARE m_adm BIGINT; sm_cad BIGINT; it_u BIGINT; it_p BIGINT; pid_adm BIGINT;
BEGIN
  INSERT INTO menu (nome, icone, ordem, ativo) VALUES ('Administração', 'fa-gear', 1, true) RETURNING id INTO m_adm;
  INSERT INTO submenu (menu_id, nome, icone, ordem, ativo) VALUES (m_adm, 'Cadastros', 'fa-folder-open', 1, true) RETURNING id INTO sm_cad;
  INSERT INTO menu_item (submenu_id, nome, icone, rota_acao, ordem, ativo)
    VALUES (sm_cad, 'Usuários', 'fa-user', 'usuario.listar', 1, true) RETURNING id INTO it_u;
  INSERT INTO menu_item (submenu_id, nome, icone, rota_acao, ordem, ativo)
    VALUES (sm_cad, 'Perfis', 'fa-id-badge', 'perfil.listar', 2, true) RETURNING id INTO it_p;
  SELECT id INTO pid_adm FROM perfil WHERE nome='Administrador';
  INSERT INTO perfil_menu_item (perfil_id, menu_item_id) VALUES (pid_adm, it_u) ON CONFLICT DO NOTHING;
  INSERT INTO perfil_menu_item (perfil_id, menu_item_id) VALUES (pid_adm, it_p) ON CONFLICT DO NOTHING;
END $$;

