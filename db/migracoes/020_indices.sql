SET search_path TO "adm", public;

CREATE INDEX IF NOT EXISTS idx_login_tentativa_email_ip ON login_tentativa (email, ip, criado_em DESC);
CREATE INDEX IF NOT EXISTS idx_log_auditoria_usuario ON log_auditoria (usuario_id, criado_em DESC);
CREATE INDEX IF NOT EXISTS idx_menu_item_rota ON menu_item (rota_acao);

