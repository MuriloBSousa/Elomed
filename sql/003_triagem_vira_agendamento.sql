-- ============================================================
-- EloMed - Migracao 003
-- Triagem gera agendamento (data/hora definidas pelo tecnico)
-- ============================================================
-- Aplicar: mysql -u root -P 3307 elomed < 003_triagem_vira_agendamento.sql
-- Idempotente: pode ser executada mais de uma vez.
-- ============================================================
-- Ao encaminhar uma triagem, o tecnico de enfermagem escolhe o
-- profissional, a data e o horario. O encaminhamento passa a gravar
-- um agendamento (status 'pendente') vinculado a triagem de origem.
-- O agendamento continua em nome do paciente: ele apenas nao escolhe
-- a data/hora nesse fluxo.
-- ============================================================

-- ------------------------------------------------------------
-- 1. Vínculo triagem -> agendamento
--    UNIQUE garante no maximo um agendamento por triagem.
-- ------------------------------------------------------------
SET @col_existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'tbl_agendamento'
       AND COLUMN_NAME = 'fk_triagem'
);
SET @sql := IF(@col_existe = 0,
    'ALTER TABLE tbl_agendamento
        ADD COLUMN fk_triagem BIGINT UNSIGNED NULL AFTER fk_paciente',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_existe := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'tbl_agendamento'
       AND INDEX_NAME = 'uq_agendamento_triagem'
);
SET @sql := IF(@idx_existe = 0,
    'ALTER TABLE tbl_agendamento
        ADD UNIQUE KEY uq_agendamento_triagem (fk_triagem)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_existe := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'tbl_agendamento'
       AND CONSTRAINT_NAME = 'fk_agendamento_triagem'
);
SET @sql := IF(@fk_existe = 0,
    'ALTER TABLE tbl_agendamento
        ADD CONSTRAINT fk_agendamento_triagem
            FOREIGN KEY (fk_triagem) REFERENCES tbl_triagem(id)
            ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 2. Duracao real das visitas criadas pelo tecnico
--    O DEFAULT (30) ficava fixo mesmo para janelas maiores.
-- ------------------------------------------------------------
UPDATE tbl_agendamento
   SET duracao_minutos = TIMESTAMPDIFF(MINUTE, data_hora_inicio, data_hora_fim)
 WHERE duracao_minutos = 30
   AND TIMESTAMPDIFF(MINUTE, data_hora_inicio, data_hora_fim) <> 30;

-- ============================================================
-- Fim da migracao 003
-- ============================================================
