-- ============================================================
-- EloMed — Migração 002
-- Triagem com avaliação prévia de IA + feedback do paciente
-- ============================================================
-- Aplicar: mysql -u root -P 3307 elomed < 002_triagem_ia_e_feedback.sql
-- Idempotente: pode ser executada mais de uma vez.
-- ============================================================

-- ------------------------------------------------------------
-- 1. Tempo de experiência do profissional
--    Baseia a recomendação da IA sobre qual profissional deve receber a visita.
-- ------------------------------------------------------------
SET @col_existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'tbl_profissional'
       AND COLUMN_NAME = 'tempo_experiencia_anos'
);
SET @sql := IF(@col_existe = 0,
    'ALTER TABLE tbl_profissional
        ADD COLUMN tempo_experiencia_anos TINYINT UNSIGNED NOT NULL DEFAULT 0
        AFTER especialidade',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Preenche a experiência dos profissionais de demonstração
-- (o Seed já grava os valores; aqui apenas cobre bases antigas).
UPDATE tbl_profissional
   SET tempo_experiencia_anos = 15
 WHERE especialidade = 'Cardiologia' AND tempo_experiencia_anos = 0;

-- ------------------------------------------------------------
-- 2. Avaliação prévia da IA na triagem
--    Gerada no momento em que o paciente solicita a triagem.
-- ------------------------------------------------------------
SET @col_existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'tbl_triagem'
       AND COLUMN_NAME = 'ia_classificacao'
);
SET @sql := IF(@col_existe = 0,
    'ALTER TABLE tbl_triagem
        ADD COLUMN ia_classificacao ENUM(''vermelho'',''amarelo'',''verde'') NULL AFTER classificacao,
        ADD COLUMN ia_justificativa_classificacao VARCHAR(500) NULL AFTER ia_classificacao,
        ADD COLUMN ia_perfil_recomendado ENUM(''medico'',''enfermeiro'') NULL AFTER ia_justificativa_classificacao,
        ADD COLUMN ia_justificativa_perfil VARCHAR(500) NULL AFTER ia_perfil_recomendado,
        ADD COLUMN ia_profissional_sugerido BIGINT UNSIGNED NULL AFTER ia_justificativa_perfil,
        ADD COLUMN ia_modelo VARCHAR(100) NULL AFTER ia_profissional_sugerido,
        ADD COLUMN ia_analisada_em DATETIME NULL AFTER ia_modelo,
        ADD KEY idx_triagem_ia_profissional (ia_profissional_sugerido),
        ADD CONSTRAINT fk_triagem_ia_profissional
            FOREIGN KEY (ia_profissional_sugerido) REFERENCES tbl_profissional(id)
            ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 3. Feedback do paciente sobre o atendimento (0 a 5 estrelas)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_feedback (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fk_atendimento BIGINT UNSIGNED NOT NULL,
    fk_profissional BIGINT UNSIGNED NOT NULL,
    fk_paciente BIGINT UNSIGNED NOT NULL,
    fk_usuario BIGINT UNSIGNED NOT NULL,
    nota TINYINT UNSIGNED NOT NULL,
    comentario VARCHAR(500) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_feedback_atendimento (fk_atendimento),
    KEY idx_feedback_profissional (fk_profissional),
    KEY idx_feedback_paciente (fk_paciente),
    KEY idx_feedback_criado (criado_em),
    CONSTRAINT chk_feedback_nota CHECK (nota BETWEEN 0 AND 5),
    CONSTRAINT fk_feedback_atendimento
        FOREIGN KEY (fk_atendimento) REFERENCES tbl_atendimento(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_feedback_profissional
        FOREIGN KEY (fk_profissional) REFERENCES tbl_profissional(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_feedback_paciente
        FOREIGN KEY (fk_paciente) REFERENCES tbl_paciente(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_feedback_usuario
        FOREIGN KEY (fk_usuario) REFERENCES tbl_usuario(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Permissões
--    feedback_visualizar  -> somente administrador
--    feedback_registrar   -> paciente
-- ------------------------------------------------------------
INSERT IGNORE INTO tbl_permissions (id, nome, slug, modulo) VALUES
(33, 'Visualizar feedback dos pacientes', 'feedback_visualizar', 'sistema'),
(34, 'Avaliar atendimento recebido', 'feedback_registrar', 'paciente');

-- Garante que o slug exista mesmo se o id já tiver sido usado por outro item.
INSERT IGNORE INTO tbl_permissions (nome, slug, modulo)
SELECT 'Visualizar feedback dos pacientes', 'feedback_visualizar', 'sistema'
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM tbl_permissions WHERE slug = 'feedback_visualizar');

INSERT IGNORE INTO tbl_permissions (nome, slug, modulo)
SELECT 'Avaliar atendimento recebido', 'feedback_registrar', 'paciente'
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM tbl_permissions WHERE slug = 'feedback_registrar');

-- feedback_visualizar (admin) e feedback_registrar (paciente)
INSERT IGNORE INTO tbl_role_permission (fk_role, fk_permission)
SELECT r.id, p.id
  FROM tbl_roles r
  JOIN tbl_permissions p
 WHERE ((r.slug = 'admin' AND p.slug = 'feedback_visualizar')
     OR (r.slug = 'paciente' AND p.slug = 'feedback_registrar'));

-- ============================================================
-- Fim da migração 002
-- ============================================================
