-- ================================================================
-- 006_foto_profissional.sql
-- Adiciona a foto do profissional de saude. Antes so o paciente
-- (tbl_paciente.foto) tinha foto; o seletor de profissional do
-- agendamento/triagem agora exibe a foto e a descricao (especialidade
-- + experiencia + registro) no menu de escolha.
-- O arquivo e salvo em public/Assets/images/usuario/ (mesma pasta
-- usada pelas fotos de paciente).
-- ================================================================

ALTER TABLE tbl_profissional
    ADD COLUMN foto VARCHAR(255) NULL AFTER limite_diario;
