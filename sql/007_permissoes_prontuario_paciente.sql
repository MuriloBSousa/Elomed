-- ================================================================
-- 007_permissoes_prontuario_paciente.sql
-- Completa a migracao 004 (prontuario do paciente): a tabela
-- tbl_prontuario_registro foi criada, mas o bloco de permissoes do
-- RBAC nunca foi executado. Sem elas o ProntuarioController recusa
-- QUALQUER acesso (RBAC::can(prontuario_paciente_acessar) = false) e
-- redireciona tudo para /painel/index.
--
-- Idempotente: pode rodar mais de uma vez sem duplicar.
-- ================================================================

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
