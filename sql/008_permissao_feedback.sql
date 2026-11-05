-- ================================================================
-- 008_permissao_feedback.sql
-- Corrige a ausencia das permissoes de feedback no banco. A migracao
-- 002 (que as criou) nao foi efetivamente aplicada ao banco em uso,
-- de modo que:
--   - /painel/feedback ficava inacessivel ate para o admin; e
--   - o paciente nao conseguia avaliar o atendimento recebido.
-- Adiciona os slugs e concede:
--   feedback_visualizar -> admin
--   feedback_registrar  -> paciente
-- Idempotente: pode rodar mais de uma vez sem duplicar.
-- ================================================================

INSERT INTO tbl_permissions (nome, slug, modulo)
SELECT 'Visualizar feedback dos pacientes', 'feedback_visualizar', 'sistema'
 WHERE NOT EXISTS (SELECT 1 FROM tbl_permissions WHERE slug = 'feedback_visualizar');

INSERT INTO tbl_permissions (nome, slug, modulo)
SELECT 'Avaliar atendimento recebido', 'feedback_registrar', 'paciente'
 WHERE NOT EXISTS (SELECT 1 FROM tbl_permissions WHERE slug = 'feedback_registrar');

INSERT IGNORE INTO tbl_role_permission (fk_role, fk_permission)
SELECT r.id, p.id
  FROM tbl_roles r
  JOIN tbl_permissions p
    ON (r.slug = 'admin' AND p.slug = 'feedback_visualizar')
    OR (r.slug = 'paciente' AND p.slug = 'feedback_registrar');
