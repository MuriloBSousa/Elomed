-- ============================================================
-- EloMed - Migracao 004
-- Prontuario do paciente (append-only, por secao) + permissoes
-- ============================================================
CREATE TABLE IF NOT EXISTS tbl_prontuario_registro (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fk_paciente BIGINT UNSIGNED NOT NULL,
    fk_usuario BIGINT UNSIGNED NOT NULL,
    fk_profissional BIGINT UNSIGNED NULL,
    fk_agendamento BIGINT UNSIGNED NULL,
    secao ENUM('anamnese','evolucao','complementar') NOT NULL,
    conteudo TEXT NOT NULL,
    status ENUM('rascunho','finalizado') NOT NULL DEFAULT 'finalizado',
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_registro_paciente_secao (fk_paciente, secao, data_hora),
    KEY idx_registro_usuario (fk_usuario),
    KEY idx_registro_profissional (fk_profissional),
    KEY idx_registro_agendamento (fk_agendamento),
    CONSTRAINT fk_registro_paciente
        FOREIGN KEY (fk_paciente) REFERENCES tbl_paciente(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_registro_usuario
        FOREIGN KEY (fk_usuario) REFERENCES tbl_usuario(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_registro_profissional
        FOREIGN KEY (fk_profissional) REFERENCES tbl_profissional(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_registro_agendamento
        FOREIGN KEY (fk_agendamento) REFERENCES tbl_agendamento(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissoes (o RBAC guarda na sessao: depois de aplicar, sair e entrar)
INSERT INTO tbl_permissions (nome, slug, modulo)
SELECT 'Consultar prontuario do paciente', 'prontuario_paciente_acessar', 'medico'
 WHERE NOT EXISTS (SELECT 1 FROM tbl_permissions
                    WHERE slug = 'prontuario_paciente_acessar');

INSERT INTO tbl_permissions (nome, slug, modulo)
SELECT 'Registrar no prontuario do paciente', 'prontuario_paciente_registrar', 'medico'
 WHERE NOT EXISTS (SELECT 1 FROM tbl_permissions
                    WHERE slug = 'prontuario_paciente_registrar');

INSERT IGNORE INTO tbl_role_permission (fk_role, fk_permission)
SELECT r.id, p.id
  FROM tbl_roles r
  JOIN tbl_permissions p
    ON p.slug IN ('prontuario_paciente_acessar', 'prontuario_paciente_registrar')
 WHERE r.slug IN ('admin', 'medico', 'enfermeiro', 'tecnico_enfermagem');
-- ============================================================
