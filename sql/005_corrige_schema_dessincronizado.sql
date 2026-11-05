-- ================================================================
-- 005_corrige_schema_dessincronizado.sql
-- Reconciliacao do banco restaurado com o codigo atual (2026-10-08).
-- O banco vivo era um restore antigo: o codigo referencia colunas e
-- tabelas (tbl_feedback, tbl_prontuario_registro, tbl_diagnostico,
-- avaliacao preliminar de IA na triagem e tempo de experiencia do
-- profissional) que nao existiam. Tipos/constraints afinados com o
-- dump canonical elomed.sql e com os models.
-- ================================================================

-- 1) tempo de experiencia do profissional (usado em criar/atualizar e
--    na classificacao de encaminhamento da IA)
ALTER TABLE tbl_profissional
    ADD COLUMN tempo_experiencia_anos TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER numero_registro;

-- 2) avaliacao preliminar da IA na solicitacao de triagem
--    (usada pela fila do tecnico e gravada por Paciente::registrarAvaliacaoIA)
ALTER TABLE tbl_triagem
    ADD COLUMN ia_classificacao ENUM('vermelho','amarelo','verde') NULL AFTER classificacao,
    ADD COLUMN ia_justificativa_classificacao VARCHAR(500) NULL AFTER ia_classificacao,
    ADD COLUMN ia_perfil_recomendado ENUM('medico','enfermeiro') NULL AFTER ia_justificativa_classificacao,
    ADD COLUMN ia_justificativa_perfil VARCHAR(500) NULL AFTER ia_perfil_recomendado,
    ADD COLUMN ia_profissional_sugerido BIGINT UNSIGNED NULL AFTER ia_justificativa_perfil,
    ADD COLUMN ia_modelo VARCHAR(100) NULL AFTER ia_profissional_sugerido,
    ADD COLUMN ia_analisada_em DATETIME NULL AFTER ia_modelo,
    ADD KEY idx_triagem_ia_profissional (ia_profissional_sugerido);

-- 3) feedback do paciente sobre o atendimento (PainelController / PacienteController)
CREATE TABLE tbl_feedback (
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
    CONSTRAINT fk_feedback_atendimento FOREIGN KEY (fk_atendimento) REFERENCES tbl_atendimento(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_feedback_profissional FOREIGN KEY (fk_profissional) REFERENCES tbl_profissional(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_feedback_paciente FOREIGN KEY (fk_paciente) REFERENCES tbl_paciente(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_feedback_usuario FOREIGN KEY (fk_usuario) REFERENCES tbl_usuario(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) lancamentos por secao do prontuario (abas anamnese/evolucao/complementar)
CREATE TABLE tbl_prontuario_registro (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fk_paciente BIGINT UNSIGNED NOT NULL,
    fk_usuario BIGINT UNSIGNED NOT NULL,
    fk_profissional BIGINT UNSIGNED NULL,
    fk_agendamento BIGINT UNSIGNED NULL,
    secao ENUM('anamnese','evolucao','complementar') NOT NULL,
    conteudo LONGTEXT NOT NULL,
    status ENUM('rascunho','finalizado') NOT NULL DEFAULT 'rascunho',
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_preg_paciente_secao (fk_paciente, secao, data_hora),
    KEY idx_preg_autor (fk_usuario),
    CONSTRAINT fk_preg_paciente FOREIGN KEY (fk_paciente) REFERENCES tbl_paciente(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_preg_usuario FOREIGN KEY (fk_usuario) REFERENCES tbl_usuario(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_preg_profissional FOREIGN KEY (fk_profissional) REFERENCES tbl_profissional(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_preg_agendamento FOREIGN KEY (fk_agendamento) REFERENCES tbl_agendamento(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) diagnosticos registrados por medico (model Diagnostico)
CREATE TABLE tbl_diagnostico (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fk_paciente BIGINT UNSIGNED NOT NULL,
    fk_medico BIGINT UNSIGNED NOT NULL,
    queixa_principal TEXT NULL,
    sintomas TEXT NULL,
    historico_medico TEXT NULL,
    sinais_vitais TEXT NULL,
    diagnostico_final TEXT NULL,
    conduta_medica TEXT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_diag_paciente (fk_paciente),
    KEY idx_diag_medico (fk_medico),
    CONSTRAINT fk_diag_paciente FOREIGN KEY (fk_paciente) REFERENCES tbl_paciente(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_diag_medico FOREIGN KEY (fk_medico) REFERENCES tbl_usuario(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;